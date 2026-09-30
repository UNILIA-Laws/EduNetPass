<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProvisioningController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [ProvisioningController::class, 'showForm'])->name('home');
    Route::post('/users', [ProvisioningController::class, 'storeSingle'])->name('users.store');
    Route::post('/upload-csv', [ProvisioningController::class, 'upload'])->name('upload.csv');
    Route::get('/download-sample-csv', [ProvisioningController::class, 'downloadSample'])->name('download.sample');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
