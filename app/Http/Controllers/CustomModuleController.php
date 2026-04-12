<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use Illuminate\Http\Request;

class CustomModuleController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.customModule';
        $this->activeSettingMenu = 'module_settings';
        $this->middleware(function ($request, $next) {
            abort_403(!(user()->permission('manage_module_setting') == 'all'));
            return $next($request);
        });
    }

    public function index()
    {
        $tab = request('tab', 'custom');

        $this->activeTab = $tab;
        $this->view = 'custom-modules.ajax.modules';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('custom-modules.index', $this->data);
    }

    public function create()
    {
        $this->activeTab = 'custom';
        $this->view = 'custom-modules.ajax.upload';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('custom-modules.index', $this->data);
    }

    public function store(Request $request)
    {
        // Handle module upload/installation
        return Reply::success(__('messages.recordSaved'));
    }

    public function show($id)
    {
        return view('custom-modules.index', $this->data);
    }

    public function edit($id)
    {
        return view('custom-modules.index', $this->data);
    }

    public function update(Request $request, $id)
    {
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroy($id)
    {
        return Reply::success(__('messages.deleteSuccess'));
    }
}
