<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'showUserList'])->name('userlist');
    Route::get('/', [AdminController::class, 'showUserList'])->name('userlist');
    Route::get('/register', [AdminController::class, 'showRegister'])->name('showregister');
    Route::post('/register', [AdminController::class, 'register'])->name('register');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/password', [PasswordController::class, 'showUpdatePassword'])->name('showupdatepassword');
    Route::post('/password', [PasswordController::class, 'updatePassword'])->name('updatepassword');

    Route::get('/users/{id}/update', [AdminController::class, 'showUpdateUser'])->name('showupdateuser');
    Route::post('/users/{id}/update', [AdminController::class, 'updateUser'])->name('updateuser');

    Route::post('/users/{id}/delete', [AdminController::class, 'deleteUser'])->name('deleteuser');
    Route::get('/users/{id}/balance', [AdminController::class, 'showUserBalance'])->name('showuserbalance');
    //ajax
    Route::post('/ajaxupdateapproved', [AdminController::class, 'ajaxUpdateApproved'])->name('ajaxupdateapproved');
    Route::post('/ajaxupdatestaffmemo', [AdminController::class, 'ajaxUpdateStaffMemo'])->name('ajaxupdatestaffmemo');

});
