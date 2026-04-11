<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDetails;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\Leave;
use App\Traits\CurrencyExchange;
use App\Traits\EmployeeDashboard;
use App\Traits\HRDashboard;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DashboardController extends AccountBaseController
{

    use AppBoot, CurrencyExchange, EmployeeDashboard, HRDashboard;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dashboard';

        $this->middleware(function ($request, $next) {
            return $next($request);
        });

    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response|mixed|void
     */
    public function index()
    {

        $this->isCheckScript();
        session()->forget(['qr_clock_in']);
        if (in_array('employee', user_roles())) {

            $this->viewHRDashboard = user()->permission('view_hr_dashboard');

            return $this->employeeDashboard();
        }

        if (in_array('admin', user_roles())) {
            return $this->advancedDashboard();
        }
    }

    public function widget(Request $request, $dashboardType)
    {
        $data = $request->except('_token');

        // Step 1: Reset all widgets' status to 0
        DashboardWidget::where('status', 1)
            ->where('dashboard_type', $dashboardType)
            ->update(['status' => 0]);

        // Step 2: Update the status to 1 for widgets present in the request
        if (!empty($data)) {
            DashboardWidget::where('dashboard_type', $dashboardType)
                ->whereIn('widget_name', array_keys($data))
                ->update(['status' => 1]);
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function checklist()
    {
        if (in_array('admin', user_roles())) {
            $this->isCheckScript();

            return view('dashboard.checklist', $this->data);
        }
    }

    /**
     * @return array|\Illuminate\Http\Response
     */
    public function memberDashboard()
    {
        abort_403(!in_array('employee', user_roles()));

        return $this->employeeDashboard();
    }

    public function advancedDashboard()
    {
        if (in_array('admin', user_roles()) || $this->sidebarUserPermissions['view_hr_dashboard'] == 4) {
            $this->activeTab = request('tab') ?: 'hr';
            $this->hrDashboard();

            if (request()->ajax()) {
                return $this->returnAjax($this->view);
            }

            return view('dashboard.admin', $this->data);
        }
    }

    public function clockInModal()
    {
        return view('dashboard.employee.clock_in_modal', $this->data);
    }


    public function accountUnverified()
    {
        return view('dashboard.unverified', $this->data);
    }

}
