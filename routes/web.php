<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeDocController;
use App\Http\Controllers\EmployeeDocumentExpiryController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\LeavesQuotaController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StickyNoteController;
use App\Http\Controllers\AppreciationController;
use App\Http\Controllers\EmployeeVisaController;
use App\Http\Controllers\MyCalendarController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\EmployeeShiftScheduleController;
use App\Http\Controllers\EmployeeShiftChangeRequestController;
use App\Http\Controllers\ProfileSettingController;
use App\Http\Controllers\TwoFASettingController;
use App\Http\Controllers\SmtpSettingController;
use App\Http\Controllers\ThemeSettingController;
use App\Http\Controllers\StorageSettingController;
use App\Http\Controllers\SecuritySettingController;
use App\Http\Controllers\SocialAuthSettingController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\ModuleSettingController;
use App\Http\Controllers\LanguageSettingController;
use App\Http\Controllers\SignUpSettingController;

Route::group(['middleware' => 'auth', 'prefix' => 'account'], function () {

    // Image upload
    Route::post('image/upload', [ImageController::class, 'store'])->name('image.store');

    // Account unverified, checklist
    Route::get('account-unverified', [DashboardController::class, 'accountUnverified'])->name('account_unverified');
    Route::get('checklist', [DashboardController::class, 'checklist'])->name('checklist');

    // Dashboard routes
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard-advanced', [DashboardController::class, 'advancedDashboard'])->name('dashboard.advanced');
    Route::post('dashboard/widget/{dashboardType}', [DashboardController::class, 'widget'])->name('dashboard.widget');

    // Attendance clock-in/out from DashboardController
    Route::get('attendances/clock-in-modal', [DashboardController::class, 'clockInModal'])->name('attendances.clock_in_modal');
    Route::post('attendances/store-clock-in', [DashboardController::class, 'storeClockIn'])->name('attendances.store_clock_in');
    Route::get('attendances/update-clock-in', [DashboardController::class, 'updateClockIn'])->name('attendances.update_clock_in');
    Route::get('attendances/show_clocked_hours', [DashboardController::class, 'showClockedHours'])->name('attendances.show_clocked_hours');

    // Settings
    Route::get('settings/change-language', [SettingsController::class, 'changeLanguage'])->name('settings.change_language');
    Route::resource('settings', SettingsController::class)->only(['edit', 'update', 'index', 'change_language']);

    // Employee routes
    Route::post('employees/apply-quick-action', [EmployeeController::class, 'applyQuickAction'])->name('employees.apply_quick_action');
    Route::post('employees/assignRole', [EmployeeController::class, 'assignRole'])->name('employees.assign_role');
    Route::get('employees/byDepartment/{id}', [EmployeeController::class, 'byDepartment'])->name('employees.by_department');
    Route::get('employees/invite-member', [EmployeeController::class, 'inviteMember'])->name('employees.invite_member');
    Route::get('employees/import', [EmployeeController::class, 'importMember'])->name('employees.import');
    Route::post('employees/import', [EmployeeController::class, 'importStore'])->name('employees.import.store');
    Route::post('employees/import/process', [EmployeeController::class, 'importProcess'])->name('employees.import.process');
    Route::get('import/process/{name}/{id}', [ImportController::class, 'getImportProgress'])->name('import.process.progress');
    Route::get('employees/import/exception/{name}', [ImportController::class, 'getQueueException'])->name('import.process.exception');
    Route::post('employees/send-invite', [EmployeeController::class, 'sendInvite'])->name('employees.send_invite');
    Route::post('employees/create-link', [EmployeeController::class, 'createLink'])->name('employees.create_link');
    Route::post('/get-exit-date-message', [EmployeeController::class, 'getExitDateMessage'])->name('getExitDateMessage');
    Route::resource('employees', EmployeeController::class);

    // Passport
    Route::resource('passport', PassportController::class);

    // Employee Visa
    Route::resource('employee-visa', EmployeeVisaController::class);

    // Emergency Contacts
    Route::resource('emergency-contacts', EmergencyContactController::class);

    // Employee Docs
    Route::get('employee-docs/download/{id}', [EmployeeDocController::class, 'download'])->name('employee-docs.download');
    Route::resource('employee-docs', EmployeeDocController::class);

    // Employee Document Expiries
    Route::get('employee-document-expiries/download/{id}', [EmployeeDocumentExpiryController::class, 'download'])->name('employee-document-expiries.download');
    Route::resource('employee-document-expiries', EmployeeDocumentExpiryController::class);

    // Employee Leaves (Leave Quota)
    Route::get('employee-leaves/employeeLeaveTypes/{id}', [LeavesQuotaController::class, 'employeeLeaveTypes'])->name('employee-leaves.employee_leave_types');
    Route::resource('employee-leaves', LeavesQuotaController::class);

    // Designations
    Route::get('designations/designation-hierarchy', [DesignationController::class, 'hierarchyData'])->name('designation.hierarchy');
    Route::post('designations/changeParent', [DesignationController::class, 'changeParent'])->name('designation.changeParent');
    Route::post('designations/search-filter', [DesignationController::class, 'searchFilter'])->name('designation.srchFilter');
    Route::post('designations/apply-quick-action', [DesignationController::class, 'applyQuickAction'])->name('designations.apply_quick_action');
    Route::resource('designations', DesignationController::class);

    // Departments
    Route::post('departments/apply-quick-action', [DepartmentController::class, 'applyQuickAction'])->name('departments.apply_quick_action');
    Route::get('departments/department-hierarchy', [DepartmentController::class, 'hierarchyData'])->name('department.hierarchy');
    Route::post('department/changeParent', [DepartmentController::class, 'changeParent'])->name('department.changeParent');
    Route::get('department/search', [DepartmentController::class, 'searchDepartment'])->name('departments.search');
    Route::get('department/{id}', [DepartmentController::class, 'getMembers'])->name('departments.members');
    Route::resource('departments', DepartmentController::class);

    // User Permissions
    Route::post('user-permissions/customPermissions/{id}', [UserPermissionController::class, 'customPermissions'])->name('user-permissions.custom_permissions');
    Route::post('user-permissions/resetPermissions/{id}', [UserPermissionController::class, 'resetPermissions'])->name('user-permissions.reset_permissions');
    Route::resource('user-permissions', UserPermissionController::class);

    // Promotions
    Route::resource('promotions', PromotionController::class);

    // Holidays
    Route::get('holidays/mark-holiday', [HolidayController::class, 'markHoliday'])->name('holidays.mark_holiday');
    Route::post('holidays/mark-holiday-store', [HolidayController::class, 'markDayHoliday'])->name('holidays.mark_holiday_store');
    Route::get('holidays/table-view', [HolidayController::class, 'tableView'])->name('holidays.table_view');
    Route::post('holidays/apply-quick-action', [HolidayController::class, 'applyQuickAction'])->name('holidays.apply_quick_action');
    Route::resource('holidays', HolidayController::class);

    // Leaves
    Route::get('leaves/leaves-date', [LeaveController::class, 'getDate'])->name('leaves.date');
    Route::get('leaves/personal', [LeaveController::class, 'personalLeaves'])->name('leaves.personal');
    Route::get('leaves/calendar', [LeaveController::class, 'leaveCalendar'])->name('leaves.calendar');
    Route::post('leaves/data', [LeaveController::class, 'data'])->name('leaves.data');
    Route::post('leaves/leaveAction', [LeaveController::class, 'leaveAction'])->name('leaves.leave_action');
    Route::get('leaves/show-reject-modal', [LeaveController::class, 'rejectLeave'])->name('leaves.show_reject_modal');
    Route::get('leaves/show-approved-modal', [LeaveController::class, 'approveLeave'])->name('leaves.show_approved_modal');
    Route::post('leaves/pre-approve-leave', [LeaveController::class, 'preApprove'])->name('leaves.pre_approve_leave');
    Route::post('leaves/apply-quick-action', [LeaveController::class, 'applyQuickAction'])->name('leaves.apply_quick_action');
    Route::get('leaves/view-related-leave/{id}', [LeaveController::class, 'viewRelatedLeave'])->name('leaves.view_related_leave');
    Route::get('leaves/export-all-leave', [LeaveController::class, 'exportAllLeaves'])->name('leaves.export_all_leave');
    Route::resource('leaves', LeaveController::class);

    // Appreciations
    Route::post('appreciations/apply-quick-action', [AppreciationController::class, 'applyQuickAction'])->name('appreciations.apply_quick_action');
    Route::resource('appreciations', AppreciationController::class);

    // Awards
    Route::group(['prefix' => 'appreciations'], function () {
        Route::post('awards/apply-quick-action', [AwardController::class, 'applyQuickAction'])->name('awards.apply_quick_action');
        Route::post('awards/change-status/{id?}', [AwardController::class, 'changeStatus'])->name('awards.change-status');
        Route::get('awards/quick-create', [AwardController::class, 'quickCreate'])->name('awards.quick-create');
        Route::post('awards/quick-store', [AwardController::class, 'quickStore'])->name('awards.quick-store');
        Route::resource('awards', AwardController::class);
    });

    // Attendance
    Route::get('attendances/export-attendance/{year}/{month}/{id}', [AttendanceController::class, 'exportAttendanceByMember'])->name('attendances.export_attendance');
    Route::get('attendances/export-all-attendance/{year}/{month}/{id}/{department}/{designation}', [AttendanceController::class, 'exportAllAttendance'])->name('attendances.export_all_attendance');
    Route::post('attendances/employee-data', [AttendanceController::class, 'employeeData'])->name('attendances.employee_data');
    Route::get('attendances/mark/{id}/{day}/{month}/{year}', [AttendanceController::class, 'mark'])->name('attendances.mark');
    Route::get('attendances/by-member', [AttendanceController::class, 'byMember'])->name('attendances.by_member');
    Route::get('attendances/by-hour', [AttendanceController::class, 'byHour'])->name('attendances.by_hour');
    Route::post('attendances/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendances.bulk_mark');
    Route::get('attendances/import', [AttendanceController::class, 'importAttendance'])->name('attendances.import');
    Route::post('attendances/import', [AttendanceController::class, 'importStore'])->name('attendances.import.store');
    Route::post('attendances/import/process', [AttendanceController::class, 'importProcess'])->name('attendances.import.process');
    Route::get('attendances/by-map-location', [AttendanceController::class, 'byMapLocation'])->name('attendances.by_map_location');
    Route::get('attendance/{id}/{day}/{month}/{year}', [AttendanceController::class, 'addAttendance'])->name('attendances.add-user-attendance');
    Route::post('attendances/check-half-day', [AttendanceController::class, 'checkHalfDay'])->name('attendances.check_half_day');
    Route::resource('attendances', AttendanceController::class);

    // Shifts
    Route::get('shifts/mark/{id}/{day}/{month}/{year}', [EmployeeShiftScheduleController::class, 'mark'])->name('shifts.mark');
    Route::get('shifts/export-all/{year}/{month}/{id}/{department}/{startDate}/{viewType}', [EmployeeShiftScheduleController::class, 'exportAllShift'])->name('shifts.export_all');
    Route::get('shifts/employee-shift-calendar', [EmployeeShiftScheduleController::class, 'employeeShiftCalendar'])->name('shifts.employee_shift_calendar');
    Route::post('shifts/bulk-shift', [EmployeeShiftScheduleController::class, 'bulkShift'])->name('shifts.bulk_shift');
    Route::resource('shifts', EmployeeShiftScheduleController::class);

    // Shifts Change
    Route::group(['prefix' => 'shifts'], function () {
        Route::post('shifts-change/approve_request/{id}', [EmployeeShiftChangeRequestController::class, 'approveRequest'])->name('shifts-change.approve_request');
        Route::post('shifts-change/decline_request/{id}', [EmployeeShiftChangeRequestController::class, 'declineRequest'])->name('shifts-change.decline_request');
        Route::post('shifts-change/apply-quick-action', [EmployeeShiftChangeRequestController::class, 'applyQuickAction'])->name('shifts-change.apply_quick_action');
        Route::resource('shifts-change', EmployeeShiftChangeRequestController::class);
    });

    // Leave Report
    Route::get('leave-report/leave-quota', [LeaveReportController::class, 'leaveQuota'])->name('leave-report.leave_quota');
    Route::get('leave-report/leave-quota/export-all-leave-quota/{id}/{year}/{month}', [LeavesQuotaController::class, 'exportAllLeaveQuota'])->name('leave_quota.export_all_leave_quota');
    Route::get('leave-report/leave-quota/{id}/{year}/{month}', [LeaveReportController::class, 'employeeLeaveQuota'])->name('leave-report.employee-leave-quota');
    Route::resource('leave-report', LeaveReportController::class);

    // Attendance Report
    Route::resource('attendance-report', AttendanceReportController::class);

    // My Calendar
    Route::get('my-calendar', [MyCalendarController::class, 'index'])->name('my-calendar.index');

    // Profile
    Route::resource('profile', ProfileController::class);

    // Notifications
    Route::post('show-notifications', [NotificationController::class, 'showNotifications'])->name('show_notifications');
    Route::get('all-notifications', [NotificationController::class, 'all'])->name('all-notifications');
    Route::post('mark-read', [NotificationController::class, 'markRead'])->name('mark_single_notification_read');
    Route::post('mark_notification_read', [NotificationController::class, 'markAllRead'])->name('mark_notification_read');

    // Sticky Notes
    Route::resource('sticky-notes', StickyNoteController::class);

    // Search
    Route::resource('search', SearchController::class);

    // QR Code Login
    Route::get('check-qr-login/{hash}', [AttendanceController::class, 'qrClockInOut'])->name('settings.qr-login');
    Route::post('change-qr-code-status', [AttendanceController::class, 'qrCodeStatus'])->name('settings.change-qr-code-status');

});

// Settings routes (from web-settings.php)
Route::group(['middleware' => 'auth', 'prefix' => 'account/settings'], function () {

    // Profile settings
    Route::resource('profile-settings', ProfileSettingController::class);

    // 2FA settings
    Route::get('2fa-codes-download', [TwoFASettingController::class, 'download'])->name('2fa_codes_download');
    Route::get('verify-2fa-password', [TwoFASettingController::class, 'verify'])->name('verify_2fa_password');
    Route::get('2fa-confirm', [TwoFASettingController::class, 'showConfirm'])->name('two-fa-settings.validate_confirm');
    Route::post('2fa-confirm', [TwoFASettingController::class, 'confirm'])->name('two-fa-settings.confirm');
    Route::get('2fa-email-confirm', [TwoFASettingController::class, 'showEmailConfirm'])->name('two-fa-settings.validate_email_confirm');
    Route::post('2fa-email-confirm', [TwoFASettingController::class, 'emailConfirm'])->name('two-fa-settings.email_confirm');
    Route::resource('two-fa-settings', TwoFASettingController::class);

    // SMTP settings
    Route::get('smtp-settings/show-send-test-mail-modal', [SmtpSettingController::class, 'showTestEmailModal'])->name('smtp_settings.show_send_test_mail_modal');
    Route::get('smtp-settings/send-test-mail', [SmtpSettingController::class, 'sendTestEmail'])->name('smtp_settings.send_test_mail');
    Route::resource('smtp-settings', SmtpSettingController::class);

    // Theme settings
    Route::resource('theme-settings', ThemeSettingController::class);

    // Storage settings
    Route::get('storage-settings/aws-local-to-aws-modal', [StorageSettingController::class, 'awsLocalToAwsModal'])->name('storage-settings.aws_local_to_aws_modal');
    Route::post('storage-settings/aws-local-to-aws', [StorageSettingController::class, 'moveFilesLocalToAwsS3'])->name('storage-settings.aws_local_to_aws');
    Route::get('storage-settings/storage-test-modal/{type}', [StorageSettingController::class, 'awsTestModal'])->name('storage-settings.aws_test_modal');
    Route::post('storage-settings/aws-test', [StorageSettingController::class, 'awsTest'])->name('storage-settings.aws_test');
    Route::resource('storage-settings', StorageSettingController::class);

    // Security settings
    Route::get('verify-google-recaptcha-v3', [SecuritySettingController::class, 'verify'])->name('verify_google_recaptcha_v3');
    Route::resource('security-settings', SecuritySettingController::class);

    // Social Auth settings
    Route::resource('social-auth-settings', SocialAuthSettingController::class, ['only' => ['index', 'update']]);

    // Notification settings
    Route::resource('notifications', NotificationSettingController::class);

    // Leave Type
    Route::resource('leaveType', LeaveTypeController::class);

    // Module settings
    Route::resource('module-settings', ModuleSettingController::class);

    // Language settings
    Route::get('language-settings/auto-translate', [LanguageSettingController::class, 'autoTranslate'])->name('language_settings.auto_translate');
    Route::post('language-settings/auto-translate', [LanguageSettingController::class, 'autoTranslateUpdate'])->name('language_settings.auto_translate_update');
    Route::post('language-settings/update-data/{id?}', [LanguageSettingController::class, 'updateData'])->name('language_settings.update_data');
    Route::post('language-settings/fix-translation', [LanguageSettingController::class, 'fixTranslation'])->name('language_settings.fix_translation');
    Route::post('language-settings/create-en-locale', [LanguageSettingController::class, 'createEnLocale'])->name('language_settings.create_en_locale');
    Route::resource('language-settings', LanguageSettingController::class);

    // Sign up settings
    Route::resource('sign-up-settings', SignUpSettingController::class)->only(['index', 'update']);

});

Route::group(['middleware' => 'auth', 'prefix' => 'account'], function () {
    Route::resource('company-settings', SettingsController::class)->only(['edit', 'update', 'index', 'change_language']);
});