<?php


use App\Http\Controllers\AdminUserController;

use Illuminate\Support\Facades\Route;

Route::get('/admin', function () {
    return view('admin.dashboard');
});

/*Route::prefix('admin')->group(function () {

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
});*/
