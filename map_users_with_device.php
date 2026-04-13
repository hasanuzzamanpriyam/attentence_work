<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== All Users (without global scopes) ===" . PHP_EOL;
$users = \App\Models\User::withoutGlobalScopes()->get(['id','name','email','device_user_id']);

foreach($users as $u) {
    $deviceId = $u->device_user_id ?? 'NULL';
    echo "User ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, device_user_id: {$deviceId}" . PHP_EOL;
}

echo PHP_EOL . "=== Instructions ===" . PHP_EOL;
echo "To map a user, run this SQL command:" . PHP_EOL;
echo "UPDATE users SET device_user_id = '19' WHERE id = 1;  -- Replace 19 with device UID, 1 with user ID" . PHP_EOL;
echo PHP_EOL;
echo "Or use this tinker command:" . PHP_EOL;
echo "php artisan tinker" . PHP_EOL;
echo ">>> App\\Models\\User::find(1)->update(['device_user_id' => '19']);" . PHP_EOL;
