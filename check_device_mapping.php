<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Users with device_user_id ===" . PHP_EOL;
$users = \App\Models\User::withoutGlobalScopes()->whereNotNull('device_user_id')->get(['id','name','device_user_id']);
foreach($users as $u) {
    echo "User ID: {$u->id}, Name: {$u->name}, device_user_id: {$u->device_user_id}" . PHP_EOL;
}

echo PHP_EOL . "=== Attendance Raw Logs (first 10) ===" . PHP_EOL;
$logs = \App\Models\AttendanceRawLog::with('user:id,name')->take(10)->get();
foreach($logs as $l) {
    $userName = $l->user ? $l->user->name : 'NULL';
    echo "Log ID: {$l->id}, user_id: {$l->user_id}, device_id: {$l->device_id}, User: {$userName}" . PHP_EOL;
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
$totalLogs = \App\Models\AttendanceRawLog::count();
$nullUserId = \App\Models\AttendanceRawLog::whereNull('user_id')->count();
$mappedUserId = \App\Models\AttendanceRawLog::whereNotNull('user_id')->count();
echo "Total attendance logs: {$totalLogs}" . PHP_EOL;
echo "Logs with NULL user_id: {$nullUserId}" . PHP_EOL;
echo "Logs with mapped user_id: {$mappedUserId}" . PHP_EOL;
