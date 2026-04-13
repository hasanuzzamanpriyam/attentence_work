<?php

/* Setting menu routes - HR & Role/Permission only */
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ThemeSettingController;
use App\Http\Controllers\TwoFASettingController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\ModuleSettingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StorageSettingController;
use App\Http\Controllers\LanguageSettingController;
use App\Http\Controllers\SecuritySettingController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\SocialAuthSettingController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\SignUpSettingController;
use App\Http\Controllers\LeaveSettingController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\ProfileSettingController;
use App\Http\Controllers\CustomModuleController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth', 'prefix' => 'account/settings'], function () {

    // App settings
    Route::post('app-settings/deleteSessions', [AppSettingController::class, 'deleteSessions'])->name('app-settings.delete_sessions');
    Route::resource('app-settings', AppSettingController::class);
    Route::resource('profile-settings', ProfileSettingController::class);

    // 2FA routes
    Route::get('2fa-codes-download', [TwoFASettingController::class, 'download'])->name('2fa_codes_download');
    Route::get('verify-2fa-password', [TwoFASettingController::class, 'verify'])->name('verify_2fa_password');
    Route::get('2fa-confirm', [TwoFASettingController::class, 'showConfirm'])->name('two-fa-settings.validate_confirm');
    Route::post('2fa-confirm', [TwoFASettingController::class, 'confirm'])->name('two-fa-settings.confirm');
    Route::get('2fa-email-confirm', [TwoFASettingController::class, 'showEmailConfirm'])->name('two-fa-settings.validate_email_confirm');
    Route::post('2fa-email-confirm', [TwoFASettingController::class, 'emailConfirm'])->name('two-fa-settings.email_confirm');
    Route::resource('two-fa-settings', TwoFASettingController::class);

    // Profile routes
    Route::post('profile/dark-theme', [ProfileController::class, 'darkTheme'])->name('profile.dark_theme');
    Route::post('profile/updateOneSignalId', [ProfileController::class, 'updateOneSignalId'])->name('profile.update_onesignal_id');
    Route::resource('profile', ProfileController::class);

    // Notification settings
    Route::resource('notifications', NotificationSettingController::class);

    // Attendance settings
    // Route::get('check-qr-login', [AttendanceSettingController::class, 'qrClockInOut'])->name('settings.qr-login');
    // Route::post('change-qr-code-status', [AttendanceSettingController::class, 'qrCodeStatus'])->name('settings.change-qr-code-status');
    Route::resource('attendance-settings', AttendanceSettingController::class);

    // Leave settings
    Route::resource('leaves-settings', LeaveSettingController::class);
    Route::post('leaves-settings/change-permission', [LeaveSettingController::class, 'changePermission'])->name('leaves-settings.changePermission');

    // Leave type
    Route::resource('leave-types', LeaveTypeController::class);

    // Module settings
    Route::resource('module-settings', ModuleSettingController::class);

    // Language settings
    Route::resource('language-settings', LanguageSettingController::class);
    Route::post('language-settings/{id}/status', [LanguageSettingController::class, 'status'])->name('language_settings.status');

    // Theme settings
    Route::resource('theme-settings', ThemeSettingController::class);

    // Storage settings
    Route::resource('storage-settings', StorageSettingController::class);

    // Security settings
    Route::resource('security-settings', SecuritySettingController::class);

    // Social auth settings
    Route::resource('social-auth-settings', SocialAuthSettingController::class);
    Route::post('social-auth-settings/{id}/status', [SocialAuthSettingController::class, 'status'])->name('social_auth_settings.status');

    // Sign up settings
    Route::resource('sign-up-settings', SignUpSettingController::class);

    // Role & Permission routes
    Route::post('role-permission/storeRole', [RolePermissionController::class, 'storeRole'])->name('role-permissions.store_role');
    Route::post('role-permission/deleteRole', [RolePermissionController::class, 'deleteRole'])->name('role-permissions.delete_role');
    Route::post('role-permissions/permissions', [RolePermissionController::class, 'permissions'])->name('role-permissions.permissions');
    Route::post('role-permissions/customPermissions', [RolePermissionController::class, 'customPermissions'])->name('role-permissions.custom_permissions');
    Route::post('role-permissions/reset-permissions', [RolePermissionController::class, 'resetPermissions'])->name('role-permissions.reset_permissions');
    Route::resource('role-permissions', RolePermissionController::class);

    // Employee shifts
    Route::post('employee-shifts/set-default', [EmployeeShiftController::class, 'setDefaultShift'])->name('employee-shifts.set_default');
    Route::resource('employee-shifts', EmployeeShiftController::class);

    // Google auth
    Route::get('google-app-connect', [GoogleAuthController::class, 'connect'])->name('google-auth.connect');
    Route::get('google-app-disconnect', [GoogleAuthController::class, 'disconnect'])->name('google-auth.disconnect');

    // Designation settings
    Route::get('designations', [DesignationController::class, 'create'])->name('settings.designations.create');
    Route::post('designations', [DesignationController::class, 'store'])->name('settings.designations.store');

    // Custom modules
    Route::resource('custom-modules', CustomModuleController::class);

    // Company settings
    Route::resource('company-settings', SettingsController::class)->except(['destroy']);
    Route::post('company-settings/{id}', [SettingsController::class, 'updateLogo'])->name('company-settings.update_logo');
    Route::post('company-settings/{id}/stop-color', [SettingsController::class, 'updateStopColor'])->name('company-settings.update_stop_color');
    Route::get('settings/change-language', [SettingsController::class, 'changeLanguage'])->name('settings.change_language');
    Route::resource('settings', SettingsController::class)->only(['edit', 'update', 'index', 'change_language']);
});
