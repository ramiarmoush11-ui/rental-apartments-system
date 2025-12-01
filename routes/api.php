<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FirstController;
use App\Http\Controllers\NotificationController;
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
Route::post('addApartment', [ApartmentController::class, 'addApartment'])->middleware('auth:sanctum')->middleware('verifiedAccount');
Route::post('offerApartment/{id}', [ApartmentController::class, 'offerApartment'])->middleware('auth:sanctum')->middleware('verifiedAccount');
Route::post('EvaluateApartment/{id}/{number}', [ApartmentController::class, 'EvaluateApartment'])->middleware('auth:sanctum')->middleware('verifiedAccount');
Route::get('showApartment', [ApartmentController::class, 'showApartment']);
Route::get('showOneApartment/{id}', [ApartmentController::class, 'showOneApartment']);
Route::get('Show_Reservations/{id}', [ApartmentController::class, 'Show_Reservations'])->middleware('auth:sanctum')->middleware('verifiedAccount');
//Show_Reservations
//اضافة تنين و تقييم ونشوف الحجز ونشوف الشقق كلهم ونشوف وحدة والاشعارات تنين كمان 

Route::get('showNotification', [NotificationController::class, 'showNotification'])->middleware('auth:sanctum');
Route::get('showOneNotification/{id}', [NotificationController::class, 'showOneNotification'])->middleware('auth:sanctum');
//showNotification
