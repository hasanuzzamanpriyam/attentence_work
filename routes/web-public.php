<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(route('login'));
});

// Invitation routes
Route::get('/invitation/{code}', [RegisterController::class, 'invitation'])->name('invitation');
Route::post('/invitation/accept-invite', [RegisterController::class, 'acceptInvite'])->name('accept_invite');

// Language and image routes
Route::get('/change-lang/{locale}', [HomeController::class, 'changeLang'])->name('front.changeLang');
Route::get('front/show-image', [HomeController::class, 'showImage'])->name('front.public.show_image');

// Social login routes
Route::get('/redirect/{provider}', [LoginController::class, 'redirect'])->name('social_login');
Route::get('/callback/{provider}', [LoginController::class, 'callback'])->name('social_login_callback');
Route::post('check-email', [LoginController::class, 'checkEmail'])->name('check_email');
Route::post('check-code', [LoginController::class, 'checkCode'])->name('check_code');
Route::get('resend-code', [LoginController::class, 'resendCode'])->name('resend_code');

// Account setup
Route::post('setup-account', [RegisterController::class, 'setupAccount'])->name('setup_account');

// Image and file routes
Route::get('quill-image/{image}', [ImageController::class, 'getImage'])->name('image.getImage');
Route::get('cropper/{element}', [ImageController::class, 'cropper'])->name('cropper');
Route::get('file/{type}/{path}', [FileController::class, 'getFile'])->name('file.getFile');

// Sync user permissions
Route::get('sync-user-permissions', [HomeController::class, 'syncPermissions'])->name('sync_user_permissions');
