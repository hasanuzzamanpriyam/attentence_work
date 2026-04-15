<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRawLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Process raw attendance logs and create/update attendance records
     *
     * @param int|null $userId
     * @param Carbon|null $date
     * @return void
     */
    public function processAttendanceLogs($userId = null, $date = null)
    {
        $query = AttendanceRawLog::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($date) {
            $query->whereDate('timestamp', $date);
        }

        $rawLogs = $query->orderBy('timestamp')->get();

        // Group by user and date (using company timezone)
        $groupedLogs = $rawLogs->groupBy(function ($log) {
            return $log->user_id . '_' . $log->timestamp->timezone(company()->timezone)->format('Y-m-d');
        });

        foreach ($groupedLogs as $groupKey => $logs) {
            list($userId, $dateStr) = explode('_', $groupKey);

            if (!$userId) continue; // Skip unmapped logs

            $user = User::find($userId);
            if (!$user) continue;

            $date = \Carbon\Carbon::parse($dateStr);

            // Sort logs by timestamp for the day
            $dayLogs = $logs->sortBy('timestamp')->values();

            $currentIn = null;

            foreach ($dayLogs as $log) {
                if ($log->type == 1) { // Check-in
                    $currentIn = $log;
                } elseif ($log->type == 2 && $currentIn) { // Check-out for current Check-in
                    $this->createOrUpdateAttendance($user, $date, $currentIn->timestamp, $log->timestamp);
                    $currentIn = null; // Reset for next pair
                }
            }

            // Handle orphaned check-in (last punch of the day is an IN)
            if ($currentIn) {
                $this->createOrUpdateAttendance($user, $date, $currentIn->timestamp, null);
            }
        }
    }

    /**
     * Create or update attendance record
     *
     * @param User $user
     * @param Carbon $date
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return Attendance
     */
    private function createOrUpdateAttendance(User $user, Carbon $date, Carbon $clockIn, ?Carbon $clockOut)
    {
        // Check if attendance already exists for this user and specific clock-in time
        $attendance = Attendance::where('user_id', $user->id)
            ->where('clock_in_time', $clockIn->toDateTimeString())
            ->first();

        // Get IP address safely for both HTTP and console context
        $ipAddress = $this->getIpAddress();

        $data = [
            'user_id' => $user->id,
            'clock_in_time' => $clockIn,
            'clock_out_time' => $clockOut,
            'clock_in_type' => 'biometric',
            'clock_out_type' => $clockOut ? 'biometric' : null,
            'working_from' => 'device',
            'work_from_type' => 'device',
            'clock_in_ip' => $ipAddress,
            'clock_out_ip' => $clockOut ? $ipAddress : null,
            'last_updated_by' => $user->id,
            'company_id' => $user->company_id ?? (company() ? company()->id : 1),
        ];

        if ($attendance) {
            // Update existing attendance
            $attendance->update($data);
        } else {
            // Create new attendance
            $data['added_by'] = $user->id;
            $data['date'] = $date;
            $attendance = Attendance::create($data);
        }

        return $attendance;
    }

    /**
     * Get IP address safely for both HTTP and console context
     *
     * @return string
     */
    private function getIpAddress()
    {
        try {
            if (app()->runningInConsole()) {
                return '127.0.0.1';
            }
            return request()->ip() ?? '127.0.0.1';
        } catch (\Exception $e) {
            return '127.0.0.1';
        }
    }

    /**
     * Get daily attendance summary for all users
     *
     * @param Carbon|null $date
     * @return \Illuminate\Support\Collection
     */
    public function getDailyAttendanceSummary($date = null)
    {
        $date = $date ?: Carbon::today();

        $attendances = Attendance::with('user')
            ->whereDate('clock_in_time', $date)
            ->get();

        return $attendances->map(function ($attendance) {
            return [
                'user_id' => $attendance->user_id,
                'user_name' => $attendance->user->name,
                'date' => $attendance->clock_in_time?->format('Y-m-d'),
                'clock_in' => $attendance->clock_in_time?->format('H:i:s'),
                'clock_out' => $attendance->clock_out_time?->format('H:i:s'),
                'working_hours' => $attendance->clock_in_time && $attendance->clock_out_time
                    ? $attendance->clock_out_time->diffInMinutes($attendance->clock_in_time)
                    : 0,
            ];
        });
    }

    /**
     * Get weekly attendance summary
     *
     * @param Carbon|null $startDate
     * @return \Illuminate\Support\Collection
     */
    public function getWeeklyAttendanceSummary($startDate = null)
    {
        $startDate = $startDate ?: Carbon::now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        $attendances = Attendance::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id');

        return $attendances->map(function ($userAttendances, $userId) {
            $user = $userAttendances->first()->user;
            $totalDays = $userAttendances->count();
            $totalHours = $userAttendances->sum(function ($attendance) {
                return $attendance->clock_in_time && $attendance->clock_out_time
                    ? $attendance->clock_out_time->diffInMinutes($attendance->clock_in_time)
                    : 0;
            });

            // Calculate working days in the week (Mon-Fri)
            $workingDays = 0;
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if ($date->isWeekday()) {
                    $workingDays++;
                }
            }

            $absentDays = $workingDays - $totalDays;

            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'total_present_days' => $totalDays,
                'total_working_days' => $workingDays,
                'absent_days' => max(0, $absentDays),
                'total_hours' => round($totalHours / 60, 2), // Convert to hours
                'week_start' => $startDate->format('Y-m-d'),
                'week_end' => $endDate->format('Y-m-d'),
            ];
        })->values();
    }

    /**
     * Get monthly attendance summary
     *
     * @param int|null $year
     * @param int|null $month
     * @return \Illuminate\Support\Collection
     */
    public function getMonthlyAttendanceSummary($year = null, $month = null)
    {
        $year = $year ?: Carbon::now()->year;
        $month = $month ?: Carbon::now()->month;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::with('user')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('user_id');

        return $attendances->map(function ($userAttendances, $userId) {
            $user = $userAttendances->first()->user;

            // Group by date for detailed view
            $dailyRecords = $userAttendances->groupBy(function ($attendance) {
                return $attendance->date->format('Y-m-d');
            });

            $dailyData = [];
            $totalPresentDays = 0;
            $totalHours = 0;

            foreach ($dailyRecords as $date => $dayAttendances) {
                $attendance = $dayAttendances->first();
                $workingHours = $attendance->clock_in_time && $attendance->clock_out_time
                    ? $attendance->clock_out_time->diffInMinutes($attendance->clock_in_time)
                    : 0;

                $dailyData[] = [
                    'date' => $date,
                    'clock_in' => $attendance->clock_in_time?->format('H:i:s'),
                    'clock_out' => $attendance->clock_out_time?->format('H:i:s'),
                    'working_hours' => round($workingHours / 60, 2),
                ];

                $totalPresentDays++;
                $totalHours += $workingHours;
            }

            // Calculate working days in the month
            $workingDays = 0;
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if ($date->isWeekday()) {
                    $workingDays++;
                }
            }

            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'total_present_days' => $totalPresentDays,
                'total_working_days' => $workingDays,
                'absent_days' => max(0, $workingDays - $totalPresentDays),
                'total_hours' => round($totalHours / 60, 2),
                'daily_records' => $dailyData,
            ];
        })->values();
    }
}
