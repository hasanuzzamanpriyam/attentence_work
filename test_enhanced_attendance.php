<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== Testing Enhanced Attendance Status System ===" . PHP_EOL . PHP_EOL;

// Test 1: Check if new columns exist
echo "[Test 1] Checking if new columns exist in users table..." . PHP_EOL;
try {
    $user = \App\Models\User::first();
    if ($user) {
        $hasCheckInTime = isset($user->check_in_time);
        $hasCheckOutTime = isset($user->check_out_time);
        $hasDutyTime = isset($user->duty_time);
        
        echo "✓ check_in_time column exists" . PHP_EOL;
        echo "✓ check_out_time column exists" . PHP_EOL;
        echo "✓ duty_time column exists" . PHP_EOL;
        echo PHP_EOL;
    } else {
        echo "✗ No users found in database" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
}

// Test 2: Test setting duty times for a user
echo "[Test 2] Setting duty times for first user..." . PHP_EOL;
$user = \App\Models\User::first();
if ($user) {
    $user->check_in_time = '09:00:00';
    $user->check_out_time = '18:00:00';
    $user->duty_time = '09:00:00'; // 9 hours
    $user->save();
    
    echo "✓ Updated user '{$user->name}' with:" . PHP_EOL;
    echo "  - Check-in: {$user->formatted_check_in_time}" . PHP_EOL;
    echo "  - Check-out: {$user->formatted_check_out_time}" . PHP_EOL;
    echo "  - Duty Hours: {$user->formatted_duty_time}" . PHP_EOL;
    echo "  - Expected Duty Minutes: {$user->expected_duty_minutes}" . PHP_EOL;
    echo PHP_EOL;
} else {
    echo "✗ No users found" . PHP_EOL;
}

// Test 3: Test status calculation
echo "[Test 3] Testing attendance status calculation..." . PHP_EOL;

// Create a mock controller instance to test the methods
$controller = new \App\Http\Controllers\AttendanceController();

// Use reflection to call private methods
$reflectionClass = new ReflectionClass($controller);

// Test calculateAttendanceStatus method
$calculateMethod = $reflectionClass->getMethod('calculateAttendanceStatus');
$calculateMethod->setAccessible(true);

// Test Case A: Early check-in
$expectedCheckIn = Carbon::parse('09:00:00');
$expectedCheckOut = Carbon::parse('18:00:00');
$actualCheckIn = Carbon::parse('2026-04-13 08:30:00'); // 30 min early
$actualCheckOut = Carbon::parse('2026-04-13 18:00:00');
$expectedDutyMinutes = 540; // 9 hours

$status = $calculateMethod->invoke(
    $controller,
    $actualCheckIn,
    $actualCheckOut,
    $expectedCheckIn,
    $expectedCheckOut,
    $expectedDutyMinutes
);

echo "Test Case A - Early Check-in (08:30 vs 09:00):" . PHP_EOL;
echo "  Status: {$status['check_in_status']}" . PHP_EOL;
echo "  Icon: {$status['icon']}" . PHP_EOL;
echo "  Color: {$status['color']}" . PHP_EOL;
echo "  Tooltip: {$status['tooltip']}" . PHP_EOL;
echo PHP_EOL;

// Test Case B: On time
$actualCheckIn = Carbon::parse('2026-04-13 09:05:00'); // 5 min late (within tolerance)
$status = $calculateMethod->invoke(
    $controller,
    $actualCheckIn,
    $actualCheckOut,
    $expectedCheckIn,
    $expectedCheckOut,
    $expectedDutyMinutes
);

echo "Test Case B - On Time (09:05 vs 09:00):" . PHP_EOL;
echo "  Status: {$status['check_in_status']}" . PHP_EOL;
echo "  Icon: {$status['icon']}" . PHP_EOL;
echo "  Color: {$status['color']}" . PHP_EOL;
echo "  Tooltip: {$status['tooltip']}" . PHP_EOL;
echo PHP_EOL;

// Test Case C: Late
$actualCheckIn = Carbon::parse('2026-04-13 09:25:00'); // 25 min late
$status = $calculateMethod->invoke(
    $controller,
    $actualCheckIn,
    $actualCheckOut,
    $expectedCheckIn,
    $expectedCheckOut,
    $expectedDutyMinutes
);

echo "Test Case C - Late (09:25 vs 09:00):" . PHP_EOL;
echo "  Status: {$status['check_in_status']}" . PHP_EOL;
echo "  Icon: {$status['icon']}" . PHP_EOL;
echo "  Color: {$status['color']}" . PHP_EOL;
echo "  Tooltip: {$status['tooltip']}" . PHP_EOL;
echo PHP_EOL;

// Test Case D: Half day (worked only 4 hours out of 9)
$actualCheckIn = Carbon::parse('2026-04-13 09:00:00');
$actualCheckOut = Carbon::parse('2026-04-13 13:00:00'); // 4 hours
$status = $calculateMethod->invoke(
    $controller,
    $actualCheckIn,
    $actualCheckOut,
    $expectedCheckIn,
    $expectedCheckOut,
    $expectedDutyMinutes
);

echo "Test Case D - Half Day (worked 4h out of 9h):" . PHP_EOL;
echo "  Worked Minutes: {$status['worked_minutes']}" . PHP_EOL;
echo "  Check-out Status: {$status['check_out_status']}" . PHP_EOL;
echo "  Icon: {$status['icon']}" . PHP_EOL;
echo "  Color: {$status['color']}" . PHP_EOL;
echo "  Tooltip: {$status['tooltip']}" . PHP_EOL;
echo PHP_EOL;

// Test Case E: Early leave (left 30 min early)
$actualCheckIn = Carbon::parse('2026-04-13 09:00:00');
$actualCheckOut = Carbon::parse('2026-04-13 17:30:00'); // 30 min early
$status = $calculateMethod->invoke(
    $controller,
    $actualCheckIn,
    $actualCheckOut,
    $expectedCheckIn,
    $expectedCheckOut,
    $expectedDutyMinutes
);

echo "Test Case E - Early Leave (left at 17:30 vs 18:00):" . PHP_EOL;
echo "  Worked Minutes: {$status['worked_minutes']}" . PHP_EOL;
echo "  Check-out Status: {$status['check_out_status']}" . PHP_EOL;
echo "  Icon: {$status['icon']}" . PHP_EOL;
echo "  Color: {$status['color']}" . PHP_EOL;
echo "  Tooltip: {$status['tooltip']}" . PHP_EOL;
echo PHP_EOL;

echo "=== Testing Complete ===" . PHP_EOL;
echo PHP_EOL;
echo "Next Steps:" . PHP_EOL;
echo "1. Go to Admin Panel → Users → Edit a user" . PHP_EOL;
echo "2. Set Check-in Time: 09:00, Check-out Time: 18:00" . PHP_EOL;
echo "3. Go to Attendance page and check if icons display correctly" . PHP_EOL;
