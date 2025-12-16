<?php

namespace App\Http\Controllers;

use App\Http\Requests\offerApartmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\PaymentProcessingTrait;
use App\Traits\BookingLogicTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use PaymentProcessingTrait, BookingLogicTrait;


    //retner                                            //$request->input('cardNumber')
    public function offerApartment(offerApartmentRequest $request, int $apartmentId)
    {
        $validated = $request->validated();
        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment does not exist',
            ], 404);
        }

        // $existingPending = Booking::where('apartment_id', $apartment->id)
        //     ->where('user_id', Auth::id())
        //     ->where('enStatus', 'Pending')
        //     ->exists();

        // if ($existingPending) {
        //     return response()->json([
        //         'message' => 'You already have a pending offer for this apartment.'
        //     ], 409);
        // }

        $isAvailable = $this->checkAvailability($validated['startTerm'], $validated['endTerm'], $apartment);
        if (!$isAvailable) {
            return response()->json([
                'message' => 'The apartment is not available for the selected dates.
                 Please choose alternative dates ',
                'data' => null
            ]);
        }
        //paying deposit 10% fome total price
        $start = Carbon::parse($validated['startTerm']);
        $end   = Carbon::parse($validated['endTerm']);
        $totalNights =  $end->diffInDays($start, true) + 1;
        //تم زيادة واحد  لانه هاد التابع لا يحسب اليوم الأخير 
        $totalPrice = $totalNights * $apartment->price;
        $deposit = $this->calculateDeposit($totalPrice);

        if (!($this->cardStatus($validated['cardNumber'], $deposit, $validated['cvv']))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);
        //test return response()->json([$ownerCardsNumber,$validated['cardNumber']]);
        $isPaid = false;

        $isPaid =  $this->payMoney($ownerCardsNumber, $validated['cardNumber'], $deposit);
        if ($isPaid == false) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Payment failed due to an issue with the owner payment system , Please try again later'
            ], 503);
        }


        //$this->refundMoney()
        /*  $Result = $apartment->users()->syncWithoutDetaching([
            Auth::id() =>
            [
                'enType'    => 'Renter',
                'enStatus'  => 'Pending',
                'rate' => null,
                'startTerm' => $validated['startTerm'],
                'endTerm'   => $validated['endTerm'],
            ]
        ]);*/

        $apartmentUser = Booking::create([
            'user_id' => Auth::id(),
            'apartment_id' => $apartment->id,
            'enType' => 'Renter',
            'enStatus' => 'Pending',
            'rate' => null,
            'startTerm' => $validated['startTerm'],
            'endTerm' => $validated['endTerm'],
            'priceAtBooking' => $apartment->price
        ]);

        Payment::create([
            'user_id' => Auth::id(),
            'booking_id'  => $apartmentUser->id,
            'amount' => $deposit,
            'cardNumber'  => $validated['cardNumber'],
        ]);

        $owner = $this->getApartmentOwner($apartment->id);
        if (!$owner) {
            return response()->json([
                'message' => 'Failed to process the reservation offer for this apartment.'
            ]);
        }
        $owner_id = $owner->id;
        Notification::create([
            'user_id' => $owner_id,
            'type'    => 'reservation_offer',
            'data'    => [
                'title'        => "New offer to your apartment",
                'apartment_id' => $apartment->id,
                'user_id'      => Auth::id(),
                'startTerm'    => $validated['startTerm'],
                'endTerm'      => $validated['endTerm'],
            ],
        ]);

        Notification::create([
            'user_id' => Auth::id(),
            'type'    => 'offer_submitted',
            'data'    => [
                'title'        => "Your offer has been submitted successfully.
         Please wait for the owner's approval.",
                'apartment_id' => $apartment->id,
                'startTerm'    => $validated['startTerm'],
                'endTerm'      => $validated['endTerm'],
            ],
        ]);
        return response()->json([
            'message' => "Your offer has been successfully submitted to the apartment owner
            please wait for their approval ",
            'data' => null
        ]);
    }

    // for the owner
    public function Show_Reservations(int $apartmentId)
    {
        $apartment = Apartment::where('id', '=', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => "apartment not found "
            ]);
        }
        $reservationsOnApartment =  Booking::where('apartment_id', $apartmentId)
            ->orderBy('startTerm', 'asc')->get();
        if ($reservationsOnApartment->isEmpty()) {
            return response()->json([
                'message' => "No reservations found for this apartment"
            ]);
        }
        return response()->json([
            'message' => null,
            'data' => $reservationsOnApartment
        ]);
    }
    public function ShowAllReservationsHistory()
    {

        $Owned_apartments = Booking::where('user_id', Auth::id())->where('enType', 'Owner');
        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');
        $reservationsOnApartments = Booking::whereIn('apartment_id', $Owned_apartments_Ids)
            ->orderBy('startTerm', 'asc')->get();
        return response()->json([
            'message' => null,
            'data' => $reservationsOnApartments
        ]);
    }
    //owner
    public function ShowAllPendingReservations()
    {
        $Owned_apartments = Booking::where('user_id', Auth::id())->where('enType', 'Owner')->get();
        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');
        $reservationsOnApartments = Booking::whereIN('apartment_id', $Owned_apartments_Ids)->where('enStatus', 'Pending')->where('enType', 'Renter')->orderBy('startTerm', 'asc')->get();
        if ($reservationsOnApartments->isEmpty()) {
            return response()->json([
                'message' => "there is no Pending Reservations yet",
                'data' => null
            ]);
        }
        return response()->json([
            'message' => null,
            'data' => $reservationsOnApartments
        ]);
    }
    public function ShowOnePendingReservations($ApartmentUserID)
    {
        $PendingReservations = Booking::where('id', $ApartmentUserID)->first();
        if (!$PendingReservations) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $owner = $this->getApartmentOwner($PendingReservations['apartment_id']);
        if ($owner && $owner['user_id'] != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json([
            'message' => null,
            'data' => $PendingReservations
        ]);
    }

    //التقييم لازم يكون rate + comment 
    public function EvaluateApartment($apartmentId, Request $request)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:1|max:5',
        ]);
        $rate = $validated['rate'];
        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment not found.'
            ], 404);
        }
        $apartmentuser = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')->first();
        if (!$apartmentuser) {
            return response()->json([
                'message' => 'You do not have an accepted reservation for this apartment.'
            ], 403);
        }
        if ($rate < 0 || $rate > 5) {
            return response()->json(['message' => 'please enter an active vlaue'], 203);
        }

        $mid_date = $this->midDate($apartmentuser['startTerm'], $apartmentuser['endTerm']);

        if (now()->lt($mid_date)) { //if (!($mid_date->lt(now()))) {انو بحب التعقيد وكذا 
            //=== mid_date->gt(now())
            return response()->json(
                ['message' => 'Thank you for your support.
             You can leave a review for the apartment once at least half of the reservation period has passed.'],
                203
            );
        }

        $apartmentuser['rate'] = $rate;
        $apartmentuser->save();
        $apartment->rate = $this->totalRateAccount($apartment->id);
        $apartment->save();

        $apartmentOwner = $this->getApartmentOwner($apartmentId);
        $owner_id = $apartmentOwner->id;

        Notification::create([
            'user_id' => $owner_id,
            'type' => " to do ....",
            'data' => [
                'title' => " new Evaluate to your apartment ",
                'apartment_id' => $apartmentId,
                'user_id' => Auth::id(),
                'rate' => $rate
            ]
        ]);

        return response()->json(['mes' => "EvaluateApartment has been successfully ", 'data' => null]);
    }

    //owner   approveReservation
    public function markReservationAwaitingPayment($BookingId)
    {
        $apartmentuser = Booking::where('id', $BookingId)->first();
        if (!$apartmentuser) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
        $owner = $this->getApartmentOwner($apartmentuser['apartment_id']);

        if (!$owner || $owner->id != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }


        $apartmentuser->update(['enStatus' => 'AwaitingPayment']);

        Notification::create([
            'user_id' => $apartmentuser['user_id'],
            'type'    => 'reservation_needs_payment',
            'data'    => [
                'title'        => "Your reservation has been approved. 
                Please complete the payment to finalize.",
                'apartment_id' => $apartmentuser['apartment_id'],
                'Reservation' => $apartmentuser
            ],
        ]);

        return response()->json(['message' => 'Approval done, awaiting payment'], 200);
    }
    //renter
    public function showReservationsAwaitingPayment()
    {
        $apartmentuser = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->get();

        if ($apartmentuser->isEmpty()) {
            return response()->json([
                'message' => 'There are no reservations awaiting payment'
            ], 404);
        }

        return response()->json([
            'message' => 'Reservations awaiting payment retrieved successfully',
            'Reservations_Awaiting_Payment'    => $apartmentuser
        ], 200);
    }

    //++ notiii
    public function userCancelReservation($BookingId)
    {

        $booking = Booking::find($BookingId); //->where('user_id', Auth::id())
        // ->where('enType', 'Renter')->first();

        $apartmentId = $booking->apartment_id;

        $apartmentOwner = $this->getApartmentOwner($apartmentId);

        if (!$booking) {
            return response()->json(['message' => 'canceling faild'], 403);
        }

        $status = $booking->enStatus;

        if ($status === 'Pending' || $status === 'AwaitingPayment') {
            return  $this->cancelPendingOrAwaitingPaymentReservation($apartmentOwner, $booking, $apartmentId);
        } elseif ($status === 'Accepted') {
            return $this->cancelAcceptedReservation($apartmentOwner, $booking, $apartmentId);
        } elseif (
            $status === 'Cancelled'
        ) {
            return response()->json(' go away -_-', 200);
        }
        ///////////////////////////////////////////////////
    }

    //helper 1// without band
    public function cancelPendingOrAwaitingPaymentReservation($apartmentOwner, $apartment_user, $apartmentId)
    {
        $totalNights =  $apartment_user->endTerm->diffInDays($apartment_user->startTerm, true) + 1;
        //تم زيادة واحد  لانه هاد التابع لا يحسب اليوم الأخير 
        $totalPrice = $totalNights * $apartment_user->priceAtBooking;
        $deposit = $this->calculateDeposit($totalPrice);
        //;
        $Payments = Auth::user()->payments;
        $cardNumbers = $Payments->pluck('cardNumber'); //هاد التابع بجيب كل ارقام البطاقات بالpayments 
        //مشان الخصم من المالك 

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);

        $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $deposit);

        if (!$FailRefund) {
            echo "damn";
        }
        $apartment_user->update(['enStatus' => 'Cancelled']);

        Notification::create([
            'user_id' => $apartment_user->user_id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "Your reservation for apartment #{$apartmentId} has been canceled. 
                               Any paid deposit has been refunded to your card.",
                'apartment_id' => $apartmentId,
            ],
        ]);

        Notification::create([
            'user_id' => $apartmentOwner->id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "A pending/awaiting payment reservation on your apartment #{$apartmentId} has been canceled by the renter.",
                'apartment_id' => $apartmentId,
                'renter_id'    => $apartment_user->user_id,
            ],
        ]);

        return response()->json([
            'message' => 'Reservaion canceld successfully'
        ], 200);
    }
    //helper 2 //within band
    public function cancelAcceptedReservation($apartmentOwner, $booking, $apartmentId)
    {
        /*if (Auth::user()->ban_type === 'Permanent') {
            return response()->json([
                'message' => 'The cancellation process could not be completed due to errors.',
            ]);
        }*/
        $totalPrice = $this->TotalPriceReservation($booking->apartment_id, $booking->startTerm, $booking->endTerm, $booking);

        $remaining90 = $this->calculateRemainingAfterDeposit($totalPrice);

        $Payments = Auth::user()->payments();
        $cardNumbers = $Payments->pluck('cardNumber');

        //مشان الخصم من المالك 
        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);


        $now = Carbon::now();
        $start = Carbon::parse($booking->startTerm)->startOfDay();
        $end   = Carbon::parse($booking->endTerm)->endOfDay();
        $before3Days = $start->copy()->subDays(3);

        $FailRefund = false;
        $refundMessage = '';

        if ($now->between($start, $end, true)) {
            // الحجز شغال حاليا الغاء بدون اي استرجاع ما رح بندو بكفي ما رجعلو شي
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = 'Reservation canceled during active period - no refund';
            $FailRefund = true;
        } elseif ($now->between($before3Days, $start, true)) {
            // ضمن اخر 3 أيام قبل البداية الغاء بدون ارجاع العربون بس رح بندو مشان يتعلم تاني مرة
            $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $remaining90);
            if (!$FailRefund) {
                //return فشل 
            }
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = 'Reservation canceled within 3 days before start - deposit not refunded';
            $this->banUser(Auth::id(), "cancel Accepted Reservation within last three_days before reservation", 30);
        } elseif ($now->isBefore($before3Days)) {
            // مستقبل بعيد الغاء بدون ارجاع العربون مافي ضرر يعني مافي باند
            $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $remaining90);
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = 'Reservation canceled in advance - deposit not refunded';
        }

        if (!$FailRefund) {
            echo "blabla";
        }

        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'accepted_reservation_canceled',
            'data'    => [
                'title'        => "Your accepted reservation for apartment #{$apartmentId} has been canceled. Any paid deposit has been refunded to your card.",
                'apartment_id' => $apartmentId,
            ],
        ]);

        Notification::create([
            'user_id' => $apartmentOwner->id,
            'type'    => 'accepted_reservation_canceled',
            'data'    => [
                'title'        => "An accepted reservation on your apartment #{$apartmentId} has been canceled by the renter.",
                'apartment_id' => $apartmentId,
                'renter_id'    => $booking->user_id,
            ],
        ]);
        return response()->json([
            'message' => 'Reservation canceled successfully',
            'details' => $refundMessage
        ], 200);
    }
    //renter
    ///$request->input('cardNumber')     // must do paymentRequest validation
    public function finalprocessPayment(PaymentRequest $request, $ApartmentUserID)
    {
        $validatedData = $request->validated();
        $apartment_user = Booking::where('id', $ApartmentUserID)
            ->where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->first();

        if (!$apartment_user) {
            return response()->json(['message' => 'Payment failed. Reservation not found'], 404);
        } //apartment
        // $apartment = Apartment::find($apartment_user['apartment_id']);
        $apartment = $apartment_user->apartment;
        if (!$apartment) {
            return response()->json(['message' => 'Payment failed. Apartment not found'], 404);
        }
        $totalPrice = $this->TotalPriceReservation($apartment_user->apartment_id, $apartment_user->startTerm, $apartment_user->endTerm, $apartment_user);

        if (!($this->cardStatus($validatedData['cardNumber'], $this->calculateRemainingAfterDeposit($totalPrice), $validatedData['cvv']))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $this->completePayment($validatedData['cardNumber'], $totalPrice * 0.9);

        Payment::create([
            'user_id' => Auth::id(),
            'booking_id'  => $apartment_user->id,
            'amount' =>  $this->calculateRemainingAfterDeposit($totalPrice),
            'cardNumber'  => $validatedData['cardNumber'],
        ]);

        $apartment_user->update(['enStatus' => 'Accepted']);

        Notification::create([
            'user_id' => Auth::id(),
            'type'    => 'payment_completed',
            'data'    => [
                'title'        => "Payment completed successfully",
                'apartment_id' => $apartment->id,
            ],
        ]);
        $owner = $this->getApartmentOwner($apartment->id);
        Notification::create([
            'user_id' => $owner->user_id,
            'type'    => 'reservation_payment_received',
            'data'    => [
                'title'        => "The renter has completed the payment for your apartment",
                'apartment_id' => $apartment->id,
            ],
        ]);
        return response()->json([
            'message' => 'Payment completed successfully. You can now receive the apartment at any time.'
        ], 200);
    }
}
//'Canclled'
