<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FirstController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;


Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');


//زبط زكاتك المديل ويرات هون اذا بتقدر تعملهن مجموعات بكون احسن المديل وير تبع الرول انا حاططلها بارمتر
//->middleware(role:admin)
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('user')->group(function () {});

    Route::prefix('apartments')->controller(ApartmentController::class)->group(function () {

        Route::post('/filter', 'filterApartments');

        Route::post('/{apartmentId}/reservations/{userId}/awaiting-payment', 'markReservationAwaitingPayment');

        Route::get('/reservations/awaiting-payment', 'showReservationsAwaitingPayment');

        Route::post('/{apartmentId}/payment', 'processPayment');

        Route::put('/{apartmentId}/reservation-status', 'updateResrevationStastus');

        Route::post('/{apartmentId}/favorites', 'addApartmentToFavoritesUser');

        Route::delete('/{apartmentId}/favorites', 'removeApartmentFromFavoritesUser');

        Route::get('/favorites', 'favourites');

        Route::post('/', 'store');

        Route::put('/{apartmentId}', 'update');

        Route::delete('/{apartmentId}', 'delete');

        Route::post('/{apartmentId}/offer', 'offerApartment');

        Route::post('/{apartmentId}/evaluate/{rate}', 'EvaluateApartment');

        Route::get('/{apartmentId}/reservations', 'Show_Reservations');

        Route::get('/', 'showApartments');

        Route::get('/{apartmentId}', 'showApartment');
    });

    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/', 'store');
        Route::put('/', 'update');
    });

    Route::get('showNotification', [NotificationController::class, 'showNotifications']);
    Route::get('showOneNotification/{id}', [NotificationController::class, 'showNotification']);
});// sanctun bracket
