<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use Carbon\Carbon;
use App\Models\UserChat;
use App\Models\UserActivity;
use Illuminate\Support\Facades\App;
use App\Traits\UniversalSearchTrait;
use Illuminate\Support\Facades\Route;

class AccountBaseController extends Controller
{

    use  UniversalSearchTrait;

    /**
     * UserBaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (!(app()->runningInConsole() || config('app.seeding'))) {
            $this->currentRouteName = request()->route()->getName();
        }

        $this->middleware(function ($request, $next) {

            // Keep this function at top
            $this->adminSpecific();

            // Call this function after adminSpecific
            $this->common();

            return $next($request);
        });
    }

    public function adminSpecific()
    {

        abort_403(!user()->admin_approval && request()->ajax());

        if (!user()->admin_approval && Route::currentRouteName() != 'account_unverified') {
            // send() is added to force redirect from here rather return to called function
            return redirect(route('account_unverified'))->send();
        }

        $this->adminTheme = admin_theme();
        $this->invoiceSetting = invoice_setting();

        $this->modules = user_modules();

        if ((in_array('messages', user_modules()))) {
            $this->unreadMessagesCount = UserChat::where('to', user()->id)
                ->where('message_seen', 'no')
                ->count();
        }

        $this->activeTimerCount = 0;
        $this->selfActiveTimer = null;
        $this->customLink = custom_link_setting();
    }

    public function common()
    {
        $this->fields = [];
        $this->languageSettings = language_setting();
        $this->pushSetting = push_setting();
        $this->smtpSetting = smtp_setting();
        $this->pusherSettings = pusher_settings();

        App::setLocale(user()->locale);
        Carbon::setLocale(user()->locale);
        setlocale(LC_TIME, user()->locale . '_' . mb_strtoupper($this->company->locale));

        $this->user = user();
        $this->unreadNotificationCount = count($this->user?->unreadNotifications);
        $this->stickyNotes = $this->user->sticky;

        $this->worksuitePlugins = worksuite_plugins();

        $this->checkListTotal = GlobalSetting::CHECKLIST_TOTAL;

        if (in_array('admin', user_roles())) {
            $this->appTheme = admin_theme();
            $this->checkListCompleted = GlobalSetting::checkListCompleted();
        }
        else if (in_array('client', user_roles())) {
            $this->appTheme = client_theme();
        }
        else {
            $this->appTheme = employee_theme();
        }

        $this->sidebarUserPermissions = sidebar_user_perms();
    }

    public function logUserActivity($userId, $text)
    {
        $activity = new UserActivity();
        $activity->user_id = $userId;
        $activity->activity = $text;
        $activity->save();
    }

}


