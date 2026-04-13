<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\AttendanceRawLog;
use App\Models\AttendanceSetting;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeviceLogController extends AccountBaseController
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        parent::__construct();
        $this->pageTitle = 'Device Logs';
        $this->activeMenu = 'attendances';
        $this->attendanceService = $attendanceService;

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('attendance', user()->modules));
            abort_403(!in_array('admin', user_roles()));
            return $next($request);
        });
    }

    public function index()
    {
        try {
            // Get today's attendance summary using the service
            $dailyAttendance = $this->attendanceService->getDailyAttendanceSummary();

            // Get raw logs for display (limit for performance)
            $rawLogs = AttendanceRawLog::with('user:id,name,email')
                ->orderBy('timestamp', 'desc')
                ->limit(1000)
                ->get();

            // Get attendance settings for working days
            $attendanceSetting = AttendanceSetting::where('company_id', company() ? company()->id : 1)->first();
            $workingDays = $attendanceSetting ? explode(',', $attendanceSetting->office_open_days) : [1, 2, 3, 4, 5]; // Default Mon-Fri

            // Current month calculation for compatibility with existing view
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

            // Group raw logs by user for the view
            $userLogs = $rawLogs->groupBy(function ($log) {
                return $log->user_id ?: 'unmapped_' . $log->device_id;
            });

            $processedData = [];
            $unmappedLogs = [];

            foreach ($userLogs as $groupKey => $userLogCollection) {
                // Check if this is a mapped user group or unmapped
                if (str_starts_with($groupKey, 'unmapped_')) {
                    $deviceUserId = str_replace('unmapped_', '', $groupKey);
                    $unmappedLogs[$deviceUserId] = $userLogCollection;
                    continue;
                }

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
                    $dayLogs = $dayLogs->sortBy('timestamp')->values();

                    // Pair punches: 1st=IN, 2nd=OUT, 3rd=IN, 4th=OUT, etc.
                    $pairs = [];
                    $totalPairs = ceil($dayLogs->count() / 2);

                    for ($i = 0; $i < $totalPairs; $i++) {
                        $clockInIndex = $i * 2;
                        $clockOutIndex = $clockInIndex + 1;

                        $clockIn = $dayLogs[$clockInIndex];
                        $clockOut = isset($dayLogs[$clockOutIndex]) ? $dayLogs[$clockOutIndex] : null;

                        $duration = 0;
                        $isCompleted = false;

                        if ($clockOut) {
                            $duration = $clockOut->timestamp->diffInMinutes($clockIn->timestamp);
                            $isCompleted = true;
                            $totalDuration += $duration;
                        }

                        $hours = intdiv($duration, 60);
                        $minutes = $duration % 60;
                        $durationFormatted = $duration > 0 ? ($hours > 0 ? $hours . 'h ' : '') . sprintf('%02dm', $minutes) : '--';

                        $pairs[] = [
                            'date' => $date,
                            'clock_in' => $clockIn->timestamp->format('H:i'),
                            'clock_in_full' => $clockIn->timestamp->format('H:i:s'),
                            'clock_out' => $clockOut ? $clockOut->timestamp->format('H:i') : '--:--',
                            'clock_out_full' => $clockOut ? $clockOut->timestamp->format('H:i:s') : null,
                            'duration_minutes' => $duration,
                            'duration_hours' => $duration > 0 ? round($duration / 60, 2) : '--',
                            'duration_formatted' => $durationFormatted,
                            'is_completed' => $isCompleted,
                            'status' => $isCompleted ? 'Completed' : 'Still Working'
                        ];
                    }

                    $dailyData = array_merge($dailyData, $pairs);
                    $totalWorkedDays++;
                }

                $absentDays = $totalWorkingDays - $totalWorkedDays;

                $totalHours = round($totalDuration / 60, 2);
                $totalDurationText = $totalDuration > 0 ? intdiv($totalDuration, 60) . 'h ' . sprintf('%02dm', $totalDuration % 60) : '--';

                $processedData[] = [
                    'user' => $user,
                    'daily_logs' => $dailyData,
                    'total_worked_days' => $totalWorkedDays,
                    'total_duration_hours' => $totalHours,
                    'total_duration_text' => $totalDurationText,
                    'absent_days' => max(0, $absentDays)
                ];
            }

            $this->processedData = $processedData;
            $this->totalWorkingDays = $totalWorkingDays;
            $this->unmappedLogs = $unmappedLogs;
            $this->dailyAttendance = $dailyAttendance;
            $this->rawLogs = $rawLogs;
            $this->workingDays = $workingDays;

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'processedData' => $this->processedData,
                        'totalWorkingDays' => $this->totalWorkingDays,
                        'unmappedLogs' => $this->unmappedLogs,
                        'dailyAttendance' => $this->dailyAttendance,
                        'rawLogs' => $this->rawLogs,
                        'workingDays' => $this->workingDays
                    ]
                ]);
            }

            return view('device-logs.index', $this->data);
        } catch (\Exception $e) {
            // Log the error and return a user-friendly message
            Log::error('Device Logs Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Set default values for the view even on error
            $this->processedData = [];
            $this->totalWorkingDays = 0;
            $this->unmappedLogs = [];
            $this->dailyAttendance = collect([]);
            $this->rawLogs = collect([]);
            $this->workingDays = [];

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while loading device logs: ' . $e->getMessage(),
                    'data' => [
                        'processedData' => $this->processedData,
                        'totalWorkingDays' => $this->totalWorkingDays,
                        'unmappedLogs' => $this->unmappedLogs,
                        'dailyAttendance' => $this->dailyAttendance,
                        'rawLogs' => $this->rawLogs,
                        'workingDays' => $this->workingDays
                    ]
                ]);
            }

            return view('device-logs.index', $this->data)->with('error', 'An error occurred while loading device logs: ' . $e->getMessage());
        }
    }

    public function sync()
    {
        try {
            $exitCode = Artisan::call('zkteco:sync');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                $message = $output ?: 'Failed to sync with the biometric device.';
                Log::error('Device Sync Failed: ' . $message);

                // Check if request expects JSON (AJAX)
                if (request()->expectsJson() || request()->ajax()) {
                    return Reply::error($message);
                }

                return redirect()->back()->with('error', $message);
            }

            Log::info('Device Sync Successful: ' . $output);

            // Parse the synced record count from output
            $syncedCount = 0;
            if (preg_match('/Synced (\d+) new records/', $output, $matches)) {
                $syncedCount = (int) $matches[1];
            }

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Device synchronized successfully. ' . $output,
                    'synced_count' => $syncedCount
                ]);
            }

            return redirect()->back()->with('success', 'Device synchronized successfully. ' . $output);
        } catch (\Exception $e) {
            Log::error('Device Sync Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $errorMessage = 'Failed to sync. Make sure device is reachable and rats/zkteco is installed. ' . $e->getMessage();

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return Reply::error($errorMessage);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Check device connectivity status
     */
    public function checkDeviceStatus()
    {
        // Get device IP and port from company settings, fallback to .env
        $company = company();
        $ip = $company->zkteco_ip ?? env('ZKTECO_IP', '192.168.0.201');
        $port = $company->zkteco_port ?? env('ZKTECO_PORT', 4370);

        $status = [
            'connected' => false,
            'ip' => $ip,
            'port' => $port,
            'message' => '',
            'device_info' => null
        ];

        try {
            // Check if library is available
            if (!class_exists(\Rats\Zkteco\Lib\ZKTeco::class)) {
                $status['message'] = 'ZKTeco library not installed. Please run: composer require rats/zkteco';
                return response()->json($status);
            }

            // Test basic connectivity
            $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
            if (!$connection) {
                $status['message'] = "Cannot connect to device at {$ip}:{$port}. Error: {$errstr}";
                return response()->json($status);
            }
            fclose($connection);

            // Try to connect with ZKTeco SDK
            $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
            $connectResult = $zk->connect();

            if (!$connectResult) {
                $status['message'] = 'Failed to establish connection with device. SDK connection failed.';
                return response()->json($status);
            }

            // Get device info
            $deviceInfo = [
                'platform' => $zk->platform(),
                'pin_width' => $zk->pinWidth(),
                'face_function_on' => $zk->faceFunctionOn(),
                'serial_number' => $zk->serialNumber(),
                'device_name' => $zk->deviceName(),
                'firmware_version' => $zk->fmVersion(),
                'os_version' => $zk->osVersion(),
                'device_time' => $zk->getTime(),
            ];

            $zk->disconnect();

            $status['connected'] = true;
            $status['message'] = 'Device is connected and ready.';
            $status['device_info'] = $deviceInfo;

            return response()->json($status);
        } catch (\Exception $e) {
            $status['message'] = 'Error checking device: ' . $e->getMessage();
            return response()->json($status);
        }
    }

    /**
     * Get the latest punch log across all users
     */
    public function getLatestPunch()
    {
        try {
            $latestLog = AttendanceRawLog::with('user:id,name,email,device_user_id')
                ->orderBy('timestamp', 'desc')
                ->first();

            if (!$latestLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'No punch logs found',
                    'data' => null
                ]);
            }

            $data = [
                'id' => $latestLog->id,
                'user_id' => $latestLog->user_id,
                'device_id' => $latestLog->device_id,
                'timestamp' => $latestLog->timestamp->format('Y-m-d H:i:s'),
                'date' => $latestLog->timestamp->format('Y-m-d'),
                'time' => $latestLog->timestamp->format('H:i:s'),
                'type' => $latestLog->type,
                'type_label' => $latestLog->type == 1 ? 'Check In' : 'Check Out',
                'user' => $latestLog->user ? [
                    'id' => $latestLog->user->id,
                    'name' => $latestLog->user->name,
                    'email' => $latestLog->user->email,
                    'device_user_id' => $latestLog->user->device_user_id,
                ] : null,
                'is_mapped' => $latestLog->user_id !== null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Latest Punch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching latest punch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed punch history for a specific user
     */
    public function getUserDetail($userId)
    {
        try {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get today's punches
            $today = now()->startOfDay();
            $punches = AttendanceRawLog::where('user_id', $userId)
                ->where('timestamp', '>=', $today)
                ->orderBy('timestamp', 'asc')
                ->get();

            $punchData = $punches->map(function ($punch) {
                return [
                    'id' => $punch->id,
                    'timestamp' => $punch->timestamp->format('Y-m-d H:i:s'),
                    'time' => $punch->timestamp->format('H:i:s'),
                    'type' => $punch->type,
                    'type_label' => $punch->type == 1 ? 'Check In' : 'Check Out',
                    'device_id' => $punch->device_id,
                ];
            });

            // Get attendance record for today if exists
            $attendance = \App\Models\Attendance::where('user_id', $userId)
                ->whereDate('date', now())
                ->first();

            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->image_url ?? null,
                ],
                'date' => now()->format('Y-m-d'),
                'punches' => $punchData,
                'total_punches' => $punchData->count(),
                'first_punch' => $punchData->first(),
                'last_punch' => $punchData->last(),
                'attendance' => $attendance ? [
                    'clock_in_time' => $attendance->clock_in_time,
                    'clock_out_time' => $attendance->clock_out_time,
                    'late' => $attendance->late,
                    'half_day' => $attendance->half_day,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('User Detail Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user detail: ' . $e->getMessage()
            ], 500);
        }
    }
}
