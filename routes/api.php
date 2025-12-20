<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/test', fn() => response()->json(['status' => 'ok']));

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::prefix('apartments')->controller(ApartmentController::class)->group(function () {
    Route::get('/', 'showApartments');
    Route::get('/filter', 'filterApartments');
    Route::get('/{apartmentId}/show', 'showApartment'); //انتبه..... لا تغير
});

/*
|--------------------------------------------------------------------------
| Protected Routes (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |-------------------- User --------------------
    */
    Route::post('/logout', [UserController::class, 'logout']);

    /*
    |-------------------- Profile --------------------
    */
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/', 'store');
        Route::post('/', 'update')->middleware('notbanned');
    });

    /*
    |-------------------- Notifications --------------------
    */
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'showNotifications');
        Route::get('/{id}', 'showNotification');
    });

    /*
    |-------------------- Apartments --------------------
    */
    Route::prefix('apartments')->group(function () {
 
        // Apartment CRUD
        Route::controller(ApartmentController::class)->group(function () {
            Route::post('/', 'store')->middleware(['notbanned', 'verifiedAccount']);
            Route::put('/{apartmentId}', 'update')->middleware('notbanned');
            Route::delete('/{apartmentId}', 'delete')->middleware('notbanned');

            /*
            |-------------------- Favorites --------------------
            */
            Route::get('/favorites', 'favourites');
            Route::post('/{apartmentId}/favorites', 'addApartmentToFavoritesUser');
            Route::delete('/{apartmentId}/favorites', 'removeApartmentFromFavoritesUser');
        });

        // Bookings & Reservations
        Route::controller(BookingController::class)->group(function () {

            /*
            |-------------------- Owner --------------------
            */
            Route::get('/{apartmentId}/reservations', 'Show_Reservations')->middleware('notbanned');
            Route::get('/reservations/history', 'ShowAllReservationsHistory');
            Route::get('/reservations/pending', 'ShowAllPendingReservations')->middleware('notbanned');
            Route::get('/reservations/{BookingId}/pending', 'ShowOnePendingReservations')->middleware('notbanned');
            Route::post('/reservations/{BookingId}/awaiting-payment', 'markReservationAwaitingPayment')->middleware('notbanned');

            /*
            |-------------------- Renter --------------------
            */
            Route::post('/{apartmentId}/offer', 'offerApartment')->middleware(['notbanned', 'verifiedAccount']);
            Route::post('/reservations/{BookingId}/final-payment', 'finalprocessPayment')->middleware(['notbanned', 'verifiedAccount']);
            Route::get('/reservations/awaiting-payment', 'showReservationsAwaitingPayment')->middleware('notbanned');
            Route::post('/{apartmentId}/evaluate', 'EvaluateApartment')->middleware(['notbanned', 'verifiedAccount']);
            Route::post('/reservations/{apartmentUserId}/cancel', 'userCancelReservation')->middleware(['notbanned', 'verifiedAccount']);//
        });
    });
});
