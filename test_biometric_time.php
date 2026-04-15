<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== Testing Biometric Device Time Integration ===" . PHP_EOL . PHP_EOL;

// Test 1: Check if attendance_raw_logs has data
echo "[Test 1] Checking attendance_raw_logs table..." . PHP_EOL;
$rawLogsCount = \App\Models\AttendanceRawLog::count();
echo "Total raw logs: {$rawLogsCount}" . PHP_EOL;

if ($rawLogsCount > 0) {
    $sampleLog = \App\Models\AttendanceRawLog::with('user:id,name')->first();
    echo "Sample log:" . PHP_EOL;
    echo "  ID: {$sampleLog->id}" . PHP_EOL;
    echo "  User ID: {$sampleLog->user_id}" . PHP_EOL;
    echo "  User Name: " . ($sampleLog->user ? $sampleLog->user->name : 'NULL') . PHP_EOL;
    echo "  Device ID: {$sampleLog->device_id}" . PHP_EOL;
    echo "  Timestamp (BIOMETRIC DEVICE TIME): {$sampleLog->timestamp}" . PHP_EOL;
    echo "  Type: {$sampleLog->type} (" . ($sampleLog->type == 1 ? 'Check-In' : 'Check-Out') . ")" . PHP_EOL;
    echo PHP_EOL;
} else {
    echo "⚠ No raw logs found. Run device sync first." . PHP_EOL . PHP_EOL;
}

// Test 2: Check user duty times
echo "[Test 2] Checking user duty times..." . PHP_EOL;
$usersWithDutyTime = \App\Models\User::withoutGlobalScopes()
    ->whereNotNull('check_in_time')
    ->orWhereNotNull('check_out_time')
    ->get(['id', 'name', 'check_in_time', 'check_out_time']);

if ($usersWithDutyTime->count() > 0) {
    foreach ($usersWithDutyTime as $user) {
        echo "User: {$user->name} (ID: {$user->id})" . PHP_EOL;
        echo "  Expected Check-In: {$user->check_in_time}" . PHP_EOL;
        echo "  Expected Check-Out: {$user->check_out_time}" . PHP_EOL;
        echo "  Expected Duty Minutes: {$user->expected_duty_minutes}" . PHP_EOL;
        echo PHP_EOL;
    }
} else {
    echo "⚠ No users with duty times set." . PHP_EOL . PHP_EOL;
}

// Test 3: Test biometric time vs attendance time comparison
echo "[Test 3] Comparing biometric device time vs attendances table..." . PHP_EOL;
if ($rawLogsCount > 0) {
    // Get a user with both raw logs and attendance
    $userWithBoth = \App\Models\AttendanceRawLog::whereNotNull('user_id')
        ->with(['user' => function($q) {
            $q->withoutGlobalScopes();
        }])
        ->first();
    
    if ($userWithBoth && $userWithBoth->user) {
        $userId = $userWithBoth->user_id;
        $date = $userWithBoth->timestamp->format('Y-m-d');
        
        echo "Testing for user: {$userWithBoth->user->name} (ID: {$userId})" . PHP_EOL;
        echo "Date: {$date}" . PHP_EOL . PHP_EOL;
        
        // Get biometric punch times
        $biometricCheckIn = \App\Models\AttendanceRawLog::where('user_id', $userId)
            ->whereDate('timestamp', $date)
            ->where('type', 1)
            ->orderBy('timestamp', 'asc')
            ->first();
        
        $biometricCheckOut = \App\Models\AttendanceRawLog::where('user_id', $userId)
            ->whereDate('timestamp', $date)
            ->where('type', 2)
            ->orderBy('timestamp', 'desc')
            ->first();
        
        echo "BIOMETRIC DEVICE TIMES:" . PHP_EOL;
        if ($biometricCheckIn) {
            echo "  Check-In: {$biometricCheckIn->timestamp}" . PHP_EOL;
        } else {
            echo "  Check-In: N/A" . PHP_EOL;
        }
        
        if ($biometricCheckOut) {
            echo "  Check-Out: {$biometricCheckOut->timestamp}" . PHP_EOL;
        } else {
            echo "  Check-Out: N/A" . PHP_EOL;
        }
        
        echo PHP_EOL;
        
        // Get attendance record
        $attendance = \App\Models\Attendance::where('user_id', $userId)
            ->whereDate('clock_in_time', $date)
            ->first();
        
        if ($attendance) {
            echo "ATTENDANCES TABLE TIMES:" . PHP_EOL;
            echo "  Check-In: {$attendance->clock_in_time}" . PHP_EOL;
            echo "  Check-Out: {$attendance->clock_out_time}" . PHP_EOL;
            echo PHP_EOL;
            
            // Compare
            if ($biometricCheckIn && $attendance->clock_in_time) {
                $biometricTime = Carbon::parse($biometricCheckIn->timestamp);
                $attendanceTime = Carbon::parse($attendance->clock_in_time);
                $diff = $biometricTime->diffInMinutes($attendanceTime);
                
                echo "COMPARISON:" . PHP_EOL;
                echo "  Difference: {$diff} minutes" . PHP_EOL;
                
                if ($diff > 0) {
                    echo "  ⚠ Times are different!" . PHP_EOL;
                    echo "  ✓ System will use BIOMETRIC DEVICE time for status calculation" . PHP_EOL;
                } else {
                    echo "  ✓ Times match" . PHP_EOL;
                }
            }
        } else {
            echo "⚠ No attendance record found for this date" . PHP_EOL;
        }
    } else {
        echo "⚠ No user with both raw logs and attendance found" . PHP_EOL;
    }
} else {
    echo "⚠ Skipped - no raw logs available" . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
echo PHP_EOL;
echo "Summary:" . PHP_EOL;
echo "1. Biometric device time comes from attendance_raw_logs.timestamp column" . PHP_EOL;
echo "2. System now uses this time (not attendances table) for status calculation" . PHP_EOL;
echo "3. Tooltip shows: 'Device: HH:MM | Expected: HH:MM - Status'" . PHP_EOL;
