<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProvisioningController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\OuController;

use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    // Landing page after login
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Campus management (declared once; the duplicate block and the "'  create'" typo are removed)
    Route::get('/campuses', [CampusController::class, 'index'])->name('campuses.index');
    Route::get('/campuses/create', [CampusController::class, 'create'])->name('campuses.create');
    Route::post('/campuses', [CampusController::class, 'store'])->name('campuses.store');
    Route::get('/campuses/{campus}/edit', [CampusController::class, 'edit'])->name('campuses.edit');
    Route::put('/campuses/{campus}', [CampusController::class, 'update'])->name('campuses.update');
    Route::delete('/campuses/{campus}', [CampusController::class, 'destroy'])->name('campuses.destroy');

    // Groups
    Route::get('/groups',                       [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups',                      [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{cn}',                  [GroupController::class, 'show'])->name('groups.show');
    Route::delete('/groups/{cn}',               [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{cn}/members',         [GroupController::class, 'addMember'])->name('groups.members.store');
    Route::delete('/groups/{cn}/members/{uid}', [GroupController::class, 'removeMember'])->name('groups.members.destroy');

    // Organizational units
    Route::get('/ous',    [OuController::class, 'index'])->name('ous.index');
    Route::post('/ous',   [OuController::class, 'store'])->name('ous.store');
    Route::delete('/ous', [OuController::class, 'destroy'])->name('ous.destroy');

    // Add users (any type): single form and bulk upload
    Route::get('/enroll', [ProvisioningController::class, 'showForm'])->name('enroll');
    Route::get('/enroll/bulk', [ProvisioningController::class, 'bulkUpload'])->name('enroll.bulk');
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