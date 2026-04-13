@echo off
echo =====================================================================
echo Device Log Fix Script for Priyam (User ID: 2)
echo =====================================================================
echo.
echo This script will guide you through fixing your device log issues.
echo.
echo STEP 1: First, let's find your device user ID
echo.
pause

echo.
echo Running query to find unmapped device IDs...
php artisan tinker --execute="echo \DB::table('attendance_raw_logs')->select('device_id', \DB::raw('COUNT(*) as count'))->whereNull('user_id')->groupBy('device_id')->orderByDesc('count')->get();"

echo.
echo.
echo Look at the output above. Note the device_id value that has the most punches.
echo.
pause

echo.
echo STEP 2: Now we'll update your user record with the device ID
echo.
echo Please enter your device_id (from the query above): 
set /p DEVICE_ID=

echo.
echo Updating user record...
php artisan tinker --execute="DB::table('users')->where('id', 2)->update(['device_user_id' => '%DEVICE_ID%']); echo 'User updated successfully!';"

echo.
echo Verifying update...
php artisan tinker --execute="echo DB::table('users')->select('id', 'name', 'email', 'device_user_id')->where('id', 2)->get();"

echo.
echo.
echo STEP 3: Fix existing type=0 records
echo.
php artisan tinker --execute="
\$logs = DB::table('attendance_raw_logs')->where('type', 0)->whereNull('user_id')->orderBy('timestamp')->get();
\$grouped = \$logs->groupBy('device_id');
foreach (\$grouped as \$deviceId => \$deviceLogs) {
    foreach (\$deviceLogs as \$index => \$log) {
        \$newType = (\$index % 2 == 0) ? 1 : 2;
        DB::table('attendance_raw_logs')->where('id', \$log->id)->update(['type' => \$newType]);
    }
}
echo 'Type values updated successfully!';
"

echo.
echo STEP 4: Map existing unmapped logs to your user
echo.
php artisan tinker --execute="
\$logs = DB::table('attendance_raw_logs')->whereNull('user_id')->get();
foreach (\$logs as \$log) {
    \$user = DB::table('users')->where('device_user_id', \$log->device_id)->first();
    if (\$user) {
        DB::table('attendance_raw_logs')->where('id', \$log->id)->update(['user_id' => \$user->id]);
    }
}
echo 'Logs mapped successfully!';
"

echo.
echo.
echo =====================================================================
echo Fix Complete! Verification:
echo =====================================================================
php artisan tinker --execute="
echo 'User: ';
echo DB::table('users')->select('id', 'name', 'device_user_id')->where('id', 2)->get();
echo PHP_EOL;
echo 'Mapped logs: ' . DB::table('attendance_raw_logs')->where('user_id', 2)->count() . PHP_EOL;
echo 'Unmapped logs: ' . DB::table('attendance_raw_logs')->whereNull('user_id')->count() . PHP_EOL;
"

echo.
echo.
echo Done! Visit http://127.0.0.1:8000/account/device-logs to see the results.
echo.
pause
