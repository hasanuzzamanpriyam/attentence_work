<?php

namespace App\Http\Controllers;

use App\DataTables\ShiftRotationDataTable;
use App\Helper\Reply;
use App\Http\Requests\EmployeeShift\StoreShiftRotationRequest;
use App\Models\AutomateShift;
use App\Models\EmployeeShift;
use App\Models\ShiftRotation;
use App\Models\ShiftRotationSequence;
use App\Models\User;
use Illuminate\Http\Request;

class ShiftRotationController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.shiftRotation';
        $this->middleware(function ($request, $next) {
            abort_403(!(user()->permission('manage_employee_shifts') == 'all' && in_array('attendance', user_modules())));
            return $next($request);
        });
    }

    public function index(ShiftRotationDataTable $dataTable)
    {
        return $dataTable->render('attendance-settings.index', $this->data);
    }

    public function create()
    {
        $this->shifts = EmployeeShift::where('shift_name', '<>', 'Day Off')->get();
        $this->employeeShifts = $this->shifts;
        $this->dates = range(1, 28);
        $this->view = 'attendance-settings.ajax.create';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('attendance-settings.create', $this->data);
    }

    public function store(Request $request)
    {
        $shiftRotation = new ShiftRotation();
        $shiftRotation->rotation_name = $request->rotation_name;
        $shiftRotation->color_code = $request->color_code ?? '#637aea';
        $shiftRotation->override_shift = $request->override_shift ?? 'no';
        $shiftRotation->send_mail = $request->send_mail ?? 'no';
        $shiftRotation->status = $request->status ?? 'active';
        $shiftRotation->save();

        // Save shift sequences
        if ($request->has('shifts') && is_array($request->shifts)) {
            foreach ($request->shifts as $index => $shiftId) {
                $sequence = new ShiftRotationSequence();
                $sequence->employee_shift_rotation_id = $shiftRotation->id;
                $sequence->employee_shift_id = $shiftId;
                $sequence->sequence = $index + 1;
                $sequence->save();
            }
        }

        return Reply::success(__('messages.recordSaved'));
    }

    public function show($id)
    {
        return redirect()->route('shift-rotations.edit', $id);
    }

    public function edit($id)
    {
        $this->shiftRotation = ShiftRotation::with('sequences.shift')->findOrFail($id);
        $this->shifts = EmployeeShift::where('shift_name', '<>', 'Day Off')->get();
        $this->employeeShifts = $this->shifts;
        $this->dates = range(1, 28);
        $this->view = 'attendance-settings.ajax.edit';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('attendance-settings.create', $this->data);
    }

    public function update(Request $request, $id)
    {
        $shiftRotation = ShiftRotation::findOrFail($id);
        $shiftRotation->rotation_name = $request->rotation_name;
        $shiftRotation->color_code = $request->color_code ?? '#637aea';
        $shiftRotation->override_shift = $request->override_shift ?? 'no';
        $shiftRotation->send_mail = $request->send_mail ?? 'no';
        $shiftRotation->status = $request->status ?? 'active';
        $shiftRotation->save();

        // Delete old sequences and create new ones
        ShiftRotationSequence::where('employee_shift_rotation_id', $id)->delete();

        if ($request->has('shifts') && is_array($request->shifts)) {
            foreach ($request->shifts as $index => $shiftId) {
                $sequence = new ShiftRotationSequence();
                $sequence->employee_shift_rotation_id = $shiftRotation->id;
                $sequence->employee_shift_id = $shiftId;
                $sequence->sequence = $index + 1;
                $sequence->save();
            }
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroy($id)
    {
        ShiftRotation::destroy($id);
        ShiftRotationSequence::where('employee_shift_rotation_id', $id)->delete();
        AutomateShift::where('employee_shift_rotation_id', $id)->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function changeStatus(Request $request)
    {
        $shiftRotation = ShiftRotation::findOrFail($request->id);
        $shiftRotation->status = $request->status;
        $shiftRotation->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function manageRotationEmployee($id)
    {
        $this->shiftRotation = ShiftRotation::findOrFail($id);
        $this->employees = User::allEmployees();
        $this->assignedEmployees = AutomateShift::where('employee_shift_rotation_id', $id)
            ->pluck('user_id')
            ->toArray();
        $this->view = 'attendance-settings.ajax.manage-employees';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('attendance-settings.create', $this->data);
    }

    public function automateShift()
    {
        $this->shiftRotations = ShiftRotation::where('status', 'active')->get();
        $this->employees = User::allEmployees();
        $this->view = 'attendance-settings.ajax.automate-shifts';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('attendance-settings.create', $this->data);
    }

    public function runRotationGet()
    {
        $this->shiftRotations = ShiftRotation::where('status', 'active')->get();
        $this->view = 'attendance-settings.ajax.run-rotation';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('attendance-settings.create', $this->data);
    }
}
