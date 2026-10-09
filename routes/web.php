<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProvisioningController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    // Landing page after login
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Add students (moved from "/" to "/enroll")
    Route::get('/enroll', [ProvisioningController::class, 'showForm'])->name('enroll');
    Route::post('/users', [ProvisioningController::class, 'storeSingle'])->name('users.store');
    Route::post('/upload-csv', [ProvisioningController::class, 'upload'])->name('upload.csv');
    Route::get('/download-sample-csv', [ProvisioningController::class, 'downloadSample'])->name('download.sample');

    // Browse / edit / reset password (users stored in LDAP)
    $uid = '[A-Za-z0-9][A-Za-z0-9._-]{0,63}';
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{uid}/edit', [UserController::class, 'edit'])->where('uid', $uid)->name('users.edit');
    Route::delete('/users/{uid}', [UserController::class, 'destroy'])->where('uid', $uid)->name('users.destroy');
    Route::put('/users/{uid}', [UserController::class, 'update'])->where('uid', $uid)->name('users.update');
    Route::post('/users/{uid}/reset-password', [UserController::class, 'resetPassword'])->where('uid', $uid)->name('users.reset');

    // Staff management
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');

    

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});