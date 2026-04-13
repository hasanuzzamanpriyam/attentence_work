<?php

/**
 * Map Users with Device IDs
 * 
 * This script maps system users to biometric device UIDs
 * 
 * Usage: php set_device_mapping.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Current Device Mappings ===" . PHP_EOL;
$users = \App\Models\User::withoutGlobalScopes()->get(['id','name','device_user_id']);
foreach($users as $u) {
    $deviceId = $u->device_user_id ?? 'NULL';
    echo "User ID: {$u->id}, Name: {$u->name}, device_user_id: {$deviceId}" . PHP_EOL;
}

echo PHP_EOL . "=== Available Device UIDs from logs ===" . PHP_EOL;
$deviceUids = \App\Models\AttendanceRawLog::distinct()->pluck('device_id')->toArray();
echo "Device UIDs: " . implode(', ', $deviceUids) . PHP_EOL;

echo PHP_EOL . "=== Mapping Instructions ===" . PHP_EOL;
echo "You need to assign each user a device_user_id from the available UIDs." . PHP_EOL;
echo PHP_EOL;
echo "Example mappings:" . PHP_EOL;
echo "  User 1 (admin-111) -> Device UID 19" . PHP_EOL;
echo "  User 2 (priyam) -> Device UID 20" . PHP_EOL;
echo "  User 3 (sanzid) -> Device UID 21" . PHP_EOL;
echo PHP_EOL;
echo "To set these mappings, run:" . PHP_EOL;
echo "  php artisan tinker" . PHP_EOL;
echo "  >>> App\\Models\\User::find(1)->update(['device_user_id' => '19']);" . PHP_EOL;
echo "  >>> App\\Models\\User::find(2)->update(['device_user_id' => '20']);" . PHP_EOL;
echo "  >>> App\\Models\\User::find(3)->update(['device_user_id' => '21']);" . PHP_EOL;
echo PHP_EOL;
echo "Or execute SQL directly:" . PHP_EOL;
echo "  UPDATE users SET device_user_id = '19' WHERE id = 1;" . PHP_EOL;
echo "  UPDATE users SET device_user_id = '20' WHERE id = 2;" . PHP_EOL;
echo "  UPDATE users SET device_user_id = '21' WHERE id = 3;" . PHP_EOL;
