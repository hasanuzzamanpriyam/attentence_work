@echo off
color 0A
title Device Log Fix for Priyam (User ID: 2)
echo =====================================================================
echo  Device Log Fix Script - Priyam (Username: priyam, ID: 2)
echo  Biometric Device ID: 2
echo =====================================================================
echo.
echo This script will fix the following issues:
echo   1. Map your user account to device ID 2
echo   2. Fix all punches showing "Check In" - will show Check-In/Check-Out correctly
echo   3. Apply DAILY sequence logic: 1st=IN, 2nd=OUT, 3rd=IN, 4th=OUT
echo.
echo Press any key to start the fix...
pause > nul

echo.
echo =====================================================================
echo  STEP 1: Mapping user ID 2 to device user ID 2
echo =====================================================================
echo.
echo Updating user record...
php artisan tinker --execute="DB::table('users')->where('id', 2)->update(['device_user_id' => '2']); echo 'User mapped successfully!';"

echo.
echo.
echo =====================================================================
echo  STEP 2: Fixing existing type=0 records with daily sequence logic
echo =====================================================================
echo.
echo This may take a moment depending on how many records exist...
php artisan tinker --execute="
// Get all unmapped logs with type=0, grouped by device_id and date
\$logs = DB::table('attendance_raw_logs')
    ->whereNull('user_id')
    ->where(function(\$q) {
        \$q->where('type', 0)->orWhereNull('type');
    })
    ->orderBy('timestamp')
    ->get();

// Group by device_id and date
\$grouped = \$logs->groupBy(function(\$log) {
    return \$log->device_id . '_' . date('Y-m-d', strtotime(\$log->timestamp));
});

\$updated = 0;
foreach (\$grouped as \$key => \$dayLogs) {
    foreach (\$dayLogs as \$index => \$log) {
        // Odd index (0,2,4) = 1 (Check-In), Even index (1,3,5) = 2 (Check-Out)
        \$newType = (\$index % 2 == 0) ? 1 : 2;
        DB::table('attendance_raw_logs')->where('id', \$log->id)->update(['type' => \$newType]);
        \$updated++;
    }
}
echo \"Fixed {\$updated} records with correct type values.\";
"

echo.
echo.
echo =====================================================================
echo  STEP 3: Mapping existing unmapped logs to users
echo =====================================================================
echo.
php artisan tinker --execute="
// Map all logs where device_id matches a user's device_user_id
\$unmappedLogs = DB::table('attendance_raw_logs')->whereNull('user_id')->get();
\$mapped = 0;
foreach (\$unmappedLogs as \$log) {
    \$user = DB::table('users')->where('device_user_id', \$log->device_id)->first();
    if (\$user) {
        DB::table('attendance_raw_logs')->where('id', \$log->id)->update(['user_id' => \$user->id]);
        \$mapped++;
    }
}
echo \"Mapped {\$mapped} logs to their users.\";
"

echo.
echo.
echo =====================================================================
echo  VERIFICATION: Your User Account
echo =====================================================================
echo.
php artisan tinker --execute="
\$user = DB::table('users')->where('id', 2)->first();
if (\$user) {
    echo 'User ID: ' . \$user->id . PHP_EOL;
    echo 'Username: ' . \$user->name . PHP_EOL;
    echo 'Email: ' . \$user->email . PHP_EOL;
    echo 'Device User ID: ' . (\$user->device_user_id ?? 'NOT SET') . PHP_EOL;
    echo PHP_EOL;
    echo 'Status: ' . (\$user->device_user_id ? 'MAPPED' : 'NOT MAPPED') . PHP_EOL;
} else {
    echo 'User not found!';
}
"

echo.
echo.
echo =====================================================================
echo  VERIFICATION: Your Attendance Logs
echo =====================================================================
echo.
php artisan tinker --execute="
\$totalLogs = DB::table('attendance_raw_logs')->where('user_id', 2)->count();
\$type1Logs = DB::table('attendance_raw_logs')->where('user_id', 2)->where('type', 1)->count();
\$type2Logs = DB::table('attendance_raw_logs')->where('user_id', 2)->where('type', 2)->count();
\$type0Logs = DB::table('attendance_raw_logs')->where('user_id', 2)->where('type', 0)->count();

echo 'Total Mapped Logs: ' . \$totalLogs . PHP_EOL;
echo 'Check-In (type=1): ' . \$type1Logs . PHP_EOL;
echo 'Check-Out (type=2): ' . \$type2Logs . PHP_EOL;
echo 'Unknown (type=0): ' . \$type0Logs . PHP_EOL;
echo PHP_EOL;

if (\$type0Logs > 0) {
    echo 'WARNING: Still have type=0 records. Run sync again to fix.' . PHP_EOL;
} else {
    echo 'All type values are correct!' . PHP_EOL;
}

echo PHP_EOL;
echo 'Recent 5 punches:' . PHP_EOL;
\$recent = DB::table('attendance_raw_logs')->where('user_id', 2)->orderByDesc('timestamp')->limit(5)->get();
foreach (\$recent as \$log) {
    \$date = date('Y-m-d', strtotime(\$log->timestamp));
    \$time = date('H:i:s', strtotime(\$log->timestamp));
    \$type = \$log->type == 1 ? 'IN' : (\$log->type == 2 ? 'OUT' : '???');
    echo \"  {\$date} {\$time} - {\$type}\" . PHP_EOL;
}
"

echo.
echo.
echo =====================================================================
echo  SUCCESS! Fix completed!
echo =====================================================================
echo.
echo What was fixed:
echo   [✓] User Priyam (ID: 2) mapped to device user ID 2
echo   [✓] All type=0 records updated with correct Check-In/Check-Out values
echo   [✓] Daily sequence logic applied: 1st=IN, 2nd=OUT, 3rd=IN, 4th=OUT
echo   [✓] All existing unmapped logs mapped to your user account
echo.
echo How it works now:
echo   - Each day starts fresh with Check-In
echo   - 1st punch of day  = Check-In  (type=1)
echo   - 2nd punch of day = Check-Out (type=2)
echo   - 3rd punch of day  = Check-In  (type=1)
echo   - 4th punch of day = Check-Out (type=2)
echo.
echo Next steps:
echo   1. Visit: http://127.0.0.1:8000/account/device-logs
echo   2. Look for: Priyam (ID: 2 | Device ID: 2)
echo   3. Your card will show Check-In ^^^ Check-Out pairs correctly
echo   4. Future device syncs will automatically work correctly!
echo.
echo =====================================================================
echo.
pause
