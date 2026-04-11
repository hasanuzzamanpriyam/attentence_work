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
        $ip = env('ZKTECO_IP', '192.168.1.201');
        $port = env('ZKTECO_PORT', 4370);

        try {
            if (class_exists(\Rats\Zkteco\Lib\ZKTeco::class)) {
                $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
                $zk->connect();
                $zk->enableDevice();

                $logs = $zk->getAttendance();
                $count = 0;

                foreach ($logs as $log) {
                    // Check if already exists to prevent duplicate
                    $exists = \App\Models\AttendanceRawLog::where('device_id', $log['uid'])
                        ->where('timestamp', $log['timestamp'])
                        ->exists();

                    if (!$exists) {
                        // Find user mapping
                        $user = \App\Models\User::where('device_user_id', $log['id'])->first();

                        if ($user) {
                            \App\Models\AttendanceRawLog::create([
                                'user_id' => $user->id,
                                'device_id' => $log['uid'],
                                'timestamp' => $log['timestamp'],
                                'type' => $log['type']
                            ]);
                            $count++;
                        }
                    }
                }

                $zk->clearAttendance(); 
                $zk->disconnect();

                $this->info("Synced {$count} new records from device.");
            } else {
                $this->error("Library rats/zkteco not found. Please install via composer.");
            }
        } catch (\Exception $e) {
            $this->error("Failed to connect to ZKTeco: " . $e->getMessage());
        }
    }
}
