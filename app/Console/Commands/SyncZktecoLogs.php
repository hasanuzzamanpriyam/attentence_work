<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncZktecoLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:sync';
    protected $description = 'Synchronize raw attendance logs from ZKTeco biometric device';

    public function handle()
    {
        // Get device IP and port from company settings, fallback to .env
        $company = company();
        $ip = $company->zkteco_ip ?? env('ZKTECO_IP', '192.168.0.201');
        $port = $company->zkteco_port ?? env('ZKTECO_PORT', 4370);

        try {
            if (!class_exists(\Rats\Zkteco\Lib\ZKTeco::class)) {
                $this->error("Library rats/zkteco not found. Please install via composer.");
                return 1;
            }

            $this->info("Testing connectivity to ZKTeco device at {$ip}:{$port}...");

            // Test if device is reachable with timeout
            $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
            if (!$connection) {
                $this->error("Cannot connect to device: {$errstr} (Error: {$errno})");
                $this->error("Please check: 1) Device is powered on, 2) IP address is correct, 3) Network connectivity");
                return 1;
            }
            fclose($connection);

            $this->info("Device is reachable. Attempting to connect...");

            $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
            $connectResult = $zk->connect();

            if (!$connectResult) {
                $this->error("Failed to establish connection with device.");
                return 1;
            }

            $zk->enableDevice();

            $this->info("Connected successfully. Retrieving attendance logs...");
            
            // AUTO-MAPPING: Get all enrolled users from device and create/map system users
            $this->info("Syncing enrolled users from device...");
            $deviceUsers = $zk->getUser();
            $this->info("Found " . count($deviceUsers) . " enrolled users on device.");
            
            // DEBUG: Show what the device returned for users
            if (!empty($deviceUsers)) {
                $this->info("Device user data structure:");
                foreach ($deviceUsers as $idx => $du) {
                    $this->info("  User {$idx}: " . json_encode($du));
                }
            }
            
            $autoMappedCount = 0;
            $autoCreatedCount = 0;
            
            foreach ($deviceUsers as $deviceUser) {
                // The getUser() returns array with keys: userid, name, cardno, uid, role, password
                $deviceUid = $deviceUser['uid'] ?? null;
                $deviceId = $deviceUser['userid'] ?? '';
                $deviceUserName = $deviceUser['name'] ?? '';
                
                if (!$deviceUid && !$deviceId) {
                    $this->warn("  ⚠ Skipping user with no UID or ID: " . json_encode($deviceUser));
                    continue;
                }
                
                // Use uid if available, fallback to userid
                $lookupId = $deviceUid ?: $deviceId;
                
                $this->info("  Processing: UID={$deviceUid}, ID={$deviceId}, Name={$deviceUserName}");
                
                // Try to find existing user by device_user_id (using UID first)
                $user = \App\Models\User::withoutGlobalScopes()
                    ->where(function($q) use ($deviceUid, $deviceId) {
                        if ($deviceUid) {
                            $q->orWhere('device_user_id', (string)$deviceUid);
                        }
                        if ($deviceId) {
                            $q->orWhere('device_user_id', (string)$deviceId);
                        }
                    })
                    ->first();
                
                // If not found, try to match by name (case-insensitive)
                if (!$user && !empty($deviceUserName)) {
                    $user = \App\Models\User::withoutGlobalScopes()
                        ->whereRaw('LOWER(name) = ?', [strtolower($deviceUserName)])
                        ->first();
                    
                    // If found by name, update their device_user_id
                    if ($user) {
                        $user->update(['device_user_id' => (string)$lookupId]);
                        $this->info("  ✓ Mapped existing user '{$user->name}' to device UID: {$lookupId}");
                        $autoMappedCount++;
                    }
                }
                
                // If still not found, create a new user automatically
                if (!$user) {
                    $userName = !empty($deviceUserName) ? $deviceUserName : "Device User {$lookupId}";
                    $userEmail = !empty($deviceId) ? "device_{$deviceId}@sync.local" : "device_uid_{$lookupId}@sync.local";
                    
                    // Check if email already exists
                    $existingEmail = \App\Models\User::withoutGlobalScopes()->where('email', $userEmail)->first();
                    if ($existingEmail) {
                        $userEmail = "device_{$lookupId}_" . time() . "@sync.local";
                    }
                    
                    $user = \App\Models\User::create([
                        'name' => $userName,
                        'email' => $userEmail,
                        'password' => bcrypt('12345678'), // Default password, should be changed by user
                        'device_user_id' => (string)$lookupId,
                        'status' => 'active',
                        'locale' => 'en',
                        'login' => 'enable',
                        'email_notifications' => 0,
                    ]);
                    
                    $this->info("  ✓ Auto-created user '{$userName}' (UID: {$lookupId})");
                    $autoCreatedCount++;
                }
            }
            
            $this->info("Auto-mapping complete: {$autoMappedCount} existing users mapped, {$autoCreatedCount} new users created.");
            $this->line("");

            $logs = $zk->getAttendance();
            $count = 0;
            $mappedCount = 0;
            $unmappedCount = 0;

            // Group logs by device user ID AND date to calculate type based on daily sequence
            $logsByDeviceUserAndDate = [];
            foreach ($logs as $log) {
                $deviceUserId = $log['id'];
                $date = date('Y-m-d', strtotime($log['timestamp']));
                $key = $deviceUserId . '_' . $date;
                
                if (!isset($logsByDeviceUserAndDate[$key])) {
                    $logsByDeviceUserAndDate[$key] = [];
                }
                $logsByDeviceUserAndDate[$key][] = $log;
            }

            // Sort each group by timestamp and assign type based on daily sequence
            $processedLogs = [];
            foreach ($logsByDeviceUserAndDate as $key => $groupLogs) {
                // Sort by timestamp
                usort($groupLogs, function ($a, $b) {
                    return strtotime($a['timestamp']) - strtotime($b['timestamp']);
                });

                // Assign type based on sequence within each day: 1st=1(IN), 2nd=2(OUT), etc.
                foreach ($groupLogs as $index => $log) {
                    // If device returns type=0, calculate it from daily sequence
                    if ($log['type'] == 0 || $log['type'] == null) {
                        $log['type'] = ($index % 2 == 0) ? 1 : 2; // Even index (0,2,4) = IN, Odd index (1,3,5) = OUT
                    }
                    $processedLogs[] = $log;
                }
            }

            foreach ($processedLogs as $log) {
                // Check if already exists to prevent duplicate
                $exists = \App\Models\AttendanceRawLog::where('device_id', $log['uid'])
                    ->where('timestamp', $log['timestamp'])
                    ->exists();

                if (!$exists) {
                    // Try to find user mapping - UID takes precedence
                    $user = \App\Models\User::where('device_user_id', (string)$log['uid'])->first();
                    
                    // If not found by UID, try enrolled ID
                    if (!$user) {
                        $user = \App\Models\User::where('device_user_id', $log['id'])->first();
                    }
                    
                    // If still not found, auto-create user (fallback)
                    if (!$user) {
                        $userName = "Device User {$log['uid']}";
                        $userEmail = "device_uid_{$log['uid']}@auto.local";
                        
                        $user = \App\Models\User::create([
                            'name' => $userName,
                            'email' => $userEmail,
                            'password' => bcrypt('12345678'), // Default password
                            'device_user_id' => (string)$log['uid'],
                            'status' => 'active',
                            'locale' => 'en',
                            'login' => 'enable',
                            'email_notifications' => 0,
                        ]);
                        
                        $this->info("  ✓ Auto-created user from attendance log: '{$userName}'");
                    }

                    $mappedCount++;

                    \App\Models\AttendanceRawLog::create([
                        'user_id' => $user ? $user->id : null,
                        'device_id' => $log['uid'],
                        'timestamp' => $log['timestamp'],
                        'type' => $log['type']
                    ]);

                    $count++;
                }
            }

            $zk->clearAttendance();
            $zk->disconnect();

            $this->info("Synced {$count} new records from device ({$mappedCount} mapped, {$unmappedCount} unmapped).");

            // Process the raw logs to create attendance records
            if ($count > 0) {
                $this->info("Processing attendance records...");
                $attendanceService = new \App\Services\AttendanceService();
                $attendanceService->processAttendanceLogs();
                $this->info("Attendance processing completed successfully.");
            } else {
                $this->info("No new records to process.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to connect to ZKTeco: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
