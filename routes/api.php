<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BookingController;


Route::get('/test', fn() => response()->json(['status' => 'ok']));

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::prefix('apartments')->controller(ApartmentController::class)->group(function () {
    Route::get('/', 'showApartments');
    Route::get('/filter', 'filterApartments');
    Route::get('/{apartmentId}', 'showApartment');
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [UserController::class, 'logout']);

  
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/', 'store');
        Route::put('/', 'update')->middleware('notbanned');
    });

 
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'showNotifications');
        Route::get('/{id}', 'showNotification');
    });

   

    Route::prefix('apartments')->group(function () {

     
        Route::controller(ApartmentController::class)->group(function () {
            Route::post('/', 'store')->middleware(['notbanned', 'verifiedAccount']);
            Route::put('/{apartmentId}', 'update')->middleware('notbanned');
            Route::delete('/{apartmentId}', 'delete')->middleware('notbanned');
        });

        Route::controller(BookingController::class)->group(function () {

            // Owner
            Route::get('/{apartmentId}/reservations', 'Show_Reservations')->middleware('notbanned');
            Route::get('/reservations/history', 'ShowAllReservationsHistory');
            Route::get('/reservations/pending', 'ShowAllPendingReservations')->middleware('notbanned');
            Route::get('/reservations/{ApartmentUserID}', 'ShowOnePendingReservations')->middleware('notbanned');
            Route::post('/reservations/{ApartmentUserID}/awaiting-payment', 'markReservationAwaitingPayment')->middleware('notbanned');

            // Renter
            Route::post('/{apartmentId}/offer', 'offerApartment')->middleware(['notbanned', 'verifiedAccount']);
            Route::post('/reservations/{ApartmentUserID}/final-payment', 'finalprocessPayment')->middleware(['notbanned', 'verifiedAccount']);
            Route::get('/reservations/awaiting-payment', 'showReservationsAwaitingPayment')->middleware('notbanned');
            Route::post('/{apartmentId}/evaluate/{rate}', 'EvaluateApartment')->middleware(['notbanned', 'verifiedAccount']);

            // Favorites
            Route::post('/{apartmentId}/favorites', 'addApartmentToFavoritesUser');
            Route::delete('/{apartmentId}/favorites', 'removeApartmentFromFavoritesUser');
            Route::get('/favorites', 'favourites');
        });

    });

});
