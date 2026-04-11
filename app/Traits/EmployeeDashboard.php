<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Leave;
use App\Models\Holiday;
use App\Models\Attendance;
use App\Models\Appreciation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\EmployeeDetails;
use App\Models\DashboardWidget;
use App\Models\EmployeeShiftSchedule;
use App\Models\Company;
use App\Models\EmployeeShift;
use Illuminate\Support\Facades\DB;

/**
 * HR-Only Employee Dashboard Trait
 */
trait EmployeeDashboard
{

    /**
     * Employee Dashboard - HR Only
     */
    public function employeeDashboard()
    {
        $user = user();

        $this->attendanceSettings = attendance_setting();

        // Check if employee has a shift
        $this->hasShift = $user->employeeDetail && $user->employeeDetail->employee_shift_id;

        // Permissions
        $this->viewEventPermission = user()->permission('view_events');
        $this->viewHolidayPermission = user()->permission('view_holiday');
        $this->viewLeavePermission = user()->permission('view_leave');
        $this->viewAttendancePermission = user()->permission('view_attendance');

        // Check today's leave
        $this->checkTodayLeave = Leave::where('status', 'approved')
            ->select('id')
            ->where('leave_date', now(company()->timezone)->toDateString())
            ->where('user_id', user()->id)
            ->where('duration', '<>', 'half day')
            ->first();

        // Check today's holiday
        $currentDate = now(company()->timezone)->format('Y-m-d');
        $this->checkTodayHoliday = Holiday::where('date', $currentDate)
            ->where(function ($query) use ($user) {
                $query->orWhere('department_id_json', 'like', '%"' . ($user->employeeDetail->department_id ?? '') . '"%')
                    ->orWhereNull('department_id_json');
            })
            ->where(function ($query) use ($user) {
                $query->orWhere('designation_id_json', 'like', '%"' . ($user->employeeDetail->designation_id ?? '') . '"%')
                    ->orWhereNull('designation_id_json');
            })
            ->first();

        // HR Stats
        $this->totalEmployees = 0;
        if (in_array('admin', user_roles())) {
            $this->totalEmployees = User::whereHas('role', function($q) {
                $q->whereHas('role', function($q2) {
                    $q2->where('name', 'employee');
                });
            })->count();
        }

        $this->totalAttendance = Attendance::whereDate('clock_in_time', now()->toDateString())->count();
        $this->totalLeaves = Leave::whereDate('leave_date', now()->toDateString())->count();

        // Load simplified view
        return view('dashboard.employee.index', $this->data);
    }

    /**
     * Widget settings
     */
    public function employeeDashboardWidget(Request $request)
    {
        return Reply::success(__('messages.updateSuccess'));
    }

    public function formatTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function attendanceShift($showClockIn = null)
    {
        return $showClockIn ?: attendance_setting();
    }

}
