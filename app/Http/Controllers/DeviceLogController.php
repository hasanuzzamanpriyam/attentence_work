<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRawLog;
use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\Artisan;

class DeviceLogController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Device Logs';
        $this->activeMenu = 'attendances';
    }

    public function index()
    {
        // Retrieve all logs (limit for performance)
        $logs = AttendanceRawLog::with('user')->orderBy('timestamp', 'asc')->limit(1000)->get();

        // Get attendance settings for working days
        $attendanceSetting = AttendanceSetting::where('company_id', company()->id)->first();
        $workingDays = $attendanceSetting ? explode(',', $attendanceSetting->office_open_days) : [1, 2, 3, 4, 5]; // Default Mon-Fri

        // Current month
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

        // Calculate total working days in current month
        $totalWorkingDays = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = \Carbon\Carbon::create($currentYear, $currentMonth, $day);
            if (in_array($date->dayOfWeekIso, $workingDays)) {
                $totalWorkingDays++;
            }
        }

        // Group by user
        $userLogs = $logs->groupBy('user_id');

        $processedData = [];

        foreach ($userLogs as $userId => $userLogCollection) {
            $user = $userLogCollection->first()->user;
            if (!$user) continue;

            // Group by date
            $dateGroups = $userLogCollection->groupBy(function ($log) {
                return $log->timestamp->format('Y-m-d');
            });

            $dailyData = [];
            $totalWorkedDays = 0;
            $totalDuration = 0;

            foreach ($dateGroups as $date => $dayLogs) {
                // Only count if in current month
                $logDate = \Carbon\Carbon::parse($date);
                if ($logDate->month != $currentMonth || $logDate->year != $currentYear) continue;

                // Sort by timestamp
                $dayLogs = $dayLogs->sortBy('timestamp');

                $firstLog = $dayLogs->first();
                $lastLog = $dayLogs->last();

                // Assume type 1 is IN, 2 is OUT
                $checkIn = $firstLog->timestamp;
                $checkOut = $lastLog->timestamp;

                $duration = $checkOut->diffInMinutes($checkIn);

                $dailyData[] = [
                    'date' => $date,
                    'first_time' => $checkIn->format('H:i'),
                    'last_time' => $checkOut->format('H:i'),
                    'duration_minutes' => $duration,
                    'all_times' => $dayLogs->map(function ($log) {
                        return $log->timestamp->format('H:i') . ' (' . ($log->type == 1 ? 'IN' : 'OUT') . ')';
                    })->toArray()
                ];

                $totalWorkedDays++;
                $totalDuration += $duration;
            }

            $absentDays = $totalWorkingDays - $totalWorkedDays;

            $processedData[] = [
                'user' => $user,
                'daily_logs' => $dailyData,
                'total_worked_days' => $totalWorkedDays,
                'total_duration_hours' => round($totalDuration / 60, 2),
                'absent_days' => max(0, $absentDays)
            ];
        }

        $this->processedData = $processedData;
        $this->totalWorkingDays = $totalWorkingDays;
        return view('device-logs.index', $this->data);
    }

    public function sync()
    {
        try {
            Artisan::call('zkteco:sync');
            $output = Artisan::output();

            // Worksuite standard Reply helper
            return \App\Helper\Reply::success('Device synchronized successfully. ' . $output);
        } catch (\Exception $e) {
            return \App\Helper\Reply::error('Failed to sync. Make sure device is reachable and rats/zkteco is installed.');
        }
    }
}
