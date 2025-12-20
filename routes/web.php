<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');
Route::prefix('admin')->middleware('admin')->group(function () {

    Route::get('/', [AdminUserController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');

    Route::get('/users/pending', [AdminUserController::class, 'pending'])
        ->name('admin.users.pending');

    Route::post('/users/{id}/approve', [AdminUserController::class, 'approve'])
        ->name('admin.users.approve');

    Route::post('/users/{id}/reject', [AdminUserController::class, 'reject'])
        ->name('admin.users.reject');

    Route::post('/users/{id}/ban', [AdminUserController::class, 'ban'])
        ->name('admin.users.ban');

    Route::post('/users/{id}/unban', [AdminUserController::class, 'unban'])
        ->name('admin.users.unban');
});
