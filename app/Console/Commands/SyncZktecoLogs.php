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
        $ip = $company->zkteco_ip ?: env('ZKTECO_IP', '192.168.0.201');
        $port = $company->zkteco_port ?: env('ZKTECO_PORT', 4370);

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
                    // Find user mapping
                    $user = \App\Models\User::where('device_user_id', $log['id'])->first();

                    if ($user) {
                        $mappedCount++;
                    } else {
                        $unmappedCount++;
                        $this->warn("Unmapped device user ID: {$log['id']} - Please map this user in the system");
                    }

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
