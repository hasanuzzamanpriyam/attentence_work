<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\AttendanceRawLog;
use App\Models\AttendanceSetting;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Artisan;

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
            $this->unmappedLogs = $unmappedLogs;
            $this->dailyAttendance = $dailyAttendance;
            $this->rawLogs = $rawLogs;
            $this->workingDays = $workingDays;

            return view('device-logs.index', $this->data);
        } catch (\Exception $e) {
            // Log the error and return a user-friendly message
            \Log::error('Device Logs Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            // Set default values for the view even on error
            $this->processedData = [];
            $this->totalWorkingDays = 0;
            $this->unmappedLogs = [];
            $this->dailyAttendance = collect([]);
            $this->rawLogs = collect([]);
            $this->workingDays = [];
            
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
                \Log::error('Device Sync Failed: ' . $message);

                // Check if request expects JSON (AJAX)
                if (request()->expectsJson() || request()->ajax()) {
                    return Reply::error($message);
                }

                return redirect()->back()->with('error', $message);
            }

            \Log::info('Device Sync Successful: ' . $output);

            // Check if request expects JSON (AJAX)
            if (request()->expectsJson() || request()->ajax()) {
                return Reply::success('Device synchronized successfully. ' . $output);
            }

            return redirect()->back()->with('success', 'Device synchronized successfully. ' . $output);
        } catch (\Exception $e) {
            \Log::error('Device Sync Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
        $ip = $company->zkteco_ip ?: env('ZKTECO_IP', '192.168.1.201');
        $port = $company->zkteco_port ?: env('ZKTECO_PORT', 4370);

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
}
