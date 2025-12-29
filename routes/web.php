<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Protected by 'admin' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('admin')->group(function () {

    // Dashboard
    Route::get('/', [AdminUserController::class, 'index'])
        ->name('admin.dashboard');

    // Users (verified only, not banned)
    Route::get('/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');

    // Registration Requests (pending users)
    Route::get('/users/pending', [AdminUserController::class, 'pending'])
        ->name('admin.users.pending');

    // Banned Users
    Route::get('/users/banned', [AdminUserController::class, 'banned'])
        ->name('admin.users.banned');

    // User Management Actions
    Route::post('/users/{id}/approve', [AdminUserController::class, 'approve'])
        ->name('admin.users.approve');

    Route::post('/users/{id}/reject', [AdminUserController::class, 'reject'])
        ->name('admin.users.reject');

    Route::post('/users/{id}/ban', [AdminUserController::class, 'ban'])
        ->name('admin.users.ban');

    Route::post('/users/{id}/unban', [AdminUserController::class, 'unban'])
        ->name('admin.users.unban');

        Route::get('/users/{id}/details', [AdminUserController::class, 'show'])
    ->name('admin.users.details');  
});