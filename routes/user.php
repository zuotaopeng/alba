<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TopController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('home', [AuthenticatedSessionController::class, 'home'])->name('home');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('register-finish', [TopController::class, 'registerFinish'])->name('register.finish');
    Route::get('guide/{tab?}', [TopController::class, 'guide'])->name('guide');

    Route::get('/', [TopController::class, 'showDashboard'])->name('showdashboard');
    Route::get('/orderlist', [OrderController::class, 'showOrderList'])->name('showorderlist');

    Route::get('/rate', [RateController::class, 'showRate'])->name('showrate');
    Route::post('/ajax-rate', [RateController::class, 'ajaxGetRate'])->name('ajaxgetrate');


    Route::get('/setting', [SettingController::class, 'showSetting'])->name('showsetting');
    Route::post('/setting', [SettingController::class, 'saveSetting'])->name('savesetting');
    Route::get('/password', [PasswordController::class, 'showPassword'])->name('showpassword');
    Route::post('/password', [PasswordController::class, 'updatePassword'])->name('updatepassword');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::post('/ajax-change-auto', [TopController::class, 'ajaxChangeAuto'])->name('ajaxchangeauto');

	Route::get('/risk', [RiskController::class, 'risk'])->name('risk');
    Route::post('/ajax-save-risk', [RiskController::class, 'ajaxSaveRisk'])->name('ajaxsaverisk');

});

Route::get('phpmyinfo', function () {
    phpinfo();
})->name('phpmyinfo');

Route::get('test', [TestController::class, 'test']);

