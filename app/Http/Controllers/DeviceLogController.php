<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRawLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeviceLogController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Device Logs';
        $this->activeMenu = 'attendances';
    }

    public function index()
    {
        // Retrieve the logs securely for the datatable
        $this->logs = AttendanceRawLog::with('user')->orderBy('timestamp', 'desc')->paginate(50);
        return view('device-logs.index', $this->data);
    }

    public function sync()
    {
        try {
            Artisan::call('zkteco:sync');
            $output = Artisan::output();
            
            // Worksuite standard Reply helper
            return \App\Helper\Reply::success('Device synchronized successfully. ' . $output);
        } catch (\Exception $e) {
            return \App\Helper\Reply::error('Failed to sync. Make sure device is reachable and rats/zkteco is installed.');
        }
    }
}
