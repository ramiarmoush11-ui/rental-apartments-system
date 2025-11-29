<?php

use App\Http\Controllers\FirstController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::post('/profile', [ProfileController::class, 'store']); //done
        Route::put('/profile', [ProfileController::class, 'update']); //done
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        //todo code
    });
});
