<?php

namespace App\Http\Controllers;

use App\Http\Requests\offerApartmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\BookingResource;
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


    //retner                                          
    public function offerApartment(offerApartmentRequest $request, int $apartmentId)
    {
        $validated = $request->validated();

        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => __('booking.not_found_apartment'),
            ], 404);
        }

        $owner = $this->getApartmentOwner($apartmentId);
        if (!$owner || $owner->id == Auth::id()) {
            return response()->json([
                'message' => __('booking.own_apartment_forbidden'),
            ], 403);
        }

        $isAvailable = $this->checkAvailability($validated['startTerm'], $validated['endTerm'], $apartment);
        if (!$isAvailable) {
            return response()->json([
                'message' => __('booking.not_available_dates'),
                'data' => null,
            ], 409);
        }

        // حساب العربون 10% من السعر الكلي
        $start = Carbon::parse($validated['startTerm']);
        $end   = Carbon::parse($validated['endTerm']);
        $totalNights = $end->diffInDays($start, true) + 1; // +1 لأن التابع لا يحسب اليوم الأخير
        $totalPrice = $totalNights * $apartment->price;
        $deposit = $this->calculateDeposit($totalPrice);

        if (!($this->cardStatus($validated['cardNumber'], $deposit, $validated['cvv']))) {
            return response()->json([
                'message' => __('booking.payment_check_failed'),
                'details' => __('booking.payment_check_details'),
            ], 503);
        }

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);

        $isPaid = $this->payMoney($ownerCardsNumber, $validated['cardNumber'], $deposit);
        if ($isPaid === false) {
            return response()->json([
                'message' => __('booking.payment_transfer_failed'),
                'details' => __('booking.payment_transfer_details'),
            ], 503);
        }

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
                'message' => __('booking.offer_process_failed'),
            ], 500);
        }

        $owner_id = $owner->id;

        Notification::create([
            'user_id' => $owner_id,
            'type'    => 'reservation_offer',
            'data'    => [
                // 'title'        => __('booking.notification_new_offer_title'),
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
                // 'title'        => __('booking.notification_offer_submitted_title'),
                'apartment_id' => $apartment->id,
                'startTerm'    => $validated['startTerm'],
                'endTerm'      => $validated['endTerm'],
            ],
        ]);

        return response()->json([
            'message' => __('booking.offer_submitted_success'),
            'data' => null,
        ], 200);
    }

    // for the owner
    public function Show_Reservations(int $apartmentId)
    {
        $apartment = Apartment::where('id', '=', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => __('booking.show_reservations_apartment_not_found')
            ], 404);
        }

        $owner = $this->getApartmentOwner($apartmentId);
        if (!$owner || $owner->id != Auth::id()) {
            return response()->json([
                'message' => __('booking.show_reservations_unauthorized')
            ], 403);
        }

        $reservationsOnApartment = Booking::where('apartment_id', $apartmentId)
            ->orderBy('startTerm', 'asc')
            ->where('enType', 'Renter')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservationsOnApartment->isEmpty()) {
            return response()->json([
                'message' => __('booking.show_reservations_empty')
            ], 404);
        }

        return response()->json([
            'message' => __('booking.show_reservations_success'),
            'data' => BookingResource::collection($reservationsOnApartment)
        ], 200);
    }
    //owner
    public function ShowAllReservationsHistory()
    {
        $Owned_apartments = Booking::where('user_id', Auth::id())
            ->where('enType', 'Owner')
            ->get();

        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');

        $reservationsOnApartments = Booking::whereIn('apartment_id', $Owned_apartments_Ids)
            ->where('enType', 'Renter')
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservationsOnApartments->isEmpty()) {
            return response()->json([
                'message' => __('booking.show_all_reservations_empty'),
                'data' => null
            ], 200);
        }

        return response()->json([
            'message' => __('booking.show_all_reservations_success'),
            'data' => BookingResource::collection($reservationsOnApartments)
        ], 200);
    }

    //owner
    public function ShowAllPendingReservations()
    {
        $Owned_apartments = Booking::where('user_id', Auth::id())
            ->where('enType', 'Owner')
            ->get();

        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');

        $reservationsOnApartments = Booking::whereIn('apartment_id', $Owned_apartments_Ids)
            ->where('enStatus', 'Pending')
            ->where('enType', 'Renter')
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservationsOnApartments->isEmpty()) {
            return response()->json([
                'message' => __('booking.show_all_pending_reservations_empty'),
                'data' => null
            ], 200);
        }

        return response()->json([
            'message' => __('booking.show_all_pending_reservations_success'),
            'data' => BookingResource::collection($reservationsOnApartments)
        ], 200);
    }

    public function ShowOnePendingReservations($BookingId)
    {
        $PendingReservations = Booking::where('id', $BookingId)
            ->where('enStatus', 'Pending')
            ->with(['apartment', 'user'])
            ->first();

        if (!$PendingReservations) {
            return response()->json([
                'message' => __('booking.show_one_pending_reservation_not_found')
            ], 404);
        }

        $owner = $this->getApartmentOwner($PendingReservations['apartment_id']);
        if (!$owner || $owner->id != Auth::id()) {
            return response()->json([
                'message' => __('booking.show_one_pending_reservation_unauthorized')
            ], 403);
        }

        return response()->json([
            'message' => __('booking.show_one_pending_reservation_success'),
            'data' => new BookingResource($PendingReservations)
        ], 200);
    }
    // owner
    public function showPendingAndAwaitingReservations()
    {
        $owned_apartment_ids = Booking::where('user_id', Auth::id())
            ->where('enType', 'Owner')
            ->pluck('apartment_id');

        $reservations = Booking::whereIn('apartment_id', $owned_apartment_ids)
            ->where('enType', 'Renter')
            ->whereIn('enStatus', ['Pending', 'AwaitingPayment'])
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.owner_pending_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.owner_pending_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }

    // owner
    public function showActiveAcceptedReservations()
    {
        $now = Carbon::now();

        $owned_apartment_ids = Booking::where('user_id', Auth::id())
            ->where('enType', 'Owner')
            ->pluck('apartment_id');

        $reservations = Booking::whereIn('apartment_id', $owned_apartment_ids)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')
            ->whereDate('endTerm', '>=', $now)
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.owner_active_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.owner_active_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }
    // owner
    public function showCancelledAndFinishedReservations()
    {
        $now = Carbon::now();

        $owned_apartment_ids = Booking::where('user_id', Auth::id())
            ->where('enType', 'Owner')
            ->pluck('apartment_id');

        $cancelled_reservations = Booking::whereIn('apartment_id', $owned_apartment_ids)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Cancelled')
            ->with(['apartment', 'user'])
            ->get();

        $finished_reservations = Booking::whereIn('apartment_id', $owned_apartment_ids)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')
            ->whereDate('endTerm', '<', $now)
            ->with(['apartment', 'user'])
            ->get();

        $reservations = $cancelled_reservations
            ->merge($finished_reservations)
            ->sortBy('startTerm')
            ->values();

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.owner_history_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.owner_history_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }

    // renter
    public function showPendingAndAwaitingReservationsRenter()
    {
        $reservations = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->whereIn('enStatus', ['Pending', 'AwaitingPayment'])
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.renter_pending_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.renter_pending_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }
    // renter
    public function showActiveAcceptedReservationsRenter()
    {
        $now = Carbon::now();

        $reservations = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')
            ->whereDate('endTerm', '>=', $now)
            ->orderBy('startTerm', 'asc')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.renter_active_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.renter_active_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }
    // renter
    public function showCancelledAndFinishedReservationsRenter()
    {
        $now = Carbon::now();

        $cancelled = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'Cancelled')
            ->with(['apartment', 'user'])
            ->get();

        $finished = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')
            ->whereDate('endTerm', '<', $now)
            ->with(['apartment', 'user'])
            ->get();

        $reservations = $cancelled
            ->merge($finished)
            ->sortBy('startTerm')
            ->values();

        if ($reservations->isEmpty()) {
            return response()->json([
                'message' => __('booking.renter_history_empty'),
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => __('booking.renter_history_success'),
            'data' => BookingResource::collection($reservations)
        ], 200);
    }

    public function EvaluateApartment($apartmentId, Request $request)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:1|max:5',
        ]);

        $rate = $validated['rate'];
        $apartment = Apartment::where('id', $apartmentId)->first();

        if (!$apartment) {
            return response()->json([
                'message' => __('booking.evaluate_apartment_not_found')
            ], 404);
        }

        $apartmentuser = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')
            ->first();

        if (!$apartmentuser) {
            return response()->json([
                'message' => __('booking.evaluate_apartment_no_accepted_reservation')
            ], 403);
        }

        if ($rate < 1 || $rate > 5) {
            return response()->json([
                'message' => __('booking.evaluate_apartment_invalid_rate')
            ], 400);
        }

        $mid_date = $this->midDate($apartmentuser['startTerm'], $apartmentuser['endTerm']);

        if (now()->lt($mid_date)) {
            return response()->json([
                'message' => __('booking.evaluate_apartment_too_early')
            ], 403);
        }

        $apartmentuser['rate'] = $rate;
        $apartmentuser->save();

        $apartment->rate = $this->totalRateAccount($apartment->id);
        $apartment->save();

        $apartmentOwner = $this->getApartmentOwner($apartmentId);
        $owner_id = $apartmentOwner->id;

        Notification::create([
            'user_id' => $owner_id,
            'type'    => 'apartment_evaluation',
            'data'    => [
                //     'title'        => __('booking.evaluate_apartment_notification_title'),
                'apartment_id' => $apartmentId,
                'user_id'      => Auth::id(),
                'rate'         => $rate
            ]
        ]);

        return response()->json([
            'message' => __('booking.evaluate_apartment_success'),
            'data'    => null
        ], 200);
    }

    //owner   approveReservation
    public function markReservationAwaitingPayment($BookingId)
    {
        $apartmentuser = Booking::where('id', $BookingId)->first();

        if (!$apartmentuser) {
            return response()->json([
                'message' => __('booking.mark_reservation_not_found')
            ], 404);
        }

        $owner = $this->getApartmentOwner($apartmentuser['apartment_id']);

        if (!$owner || $owner->id != Auth::id()) {
            return response()->json([
                'message' => __('booking.mark_reservation_unauthorized')
            ], 403);
        }

        $apartmentuser->update(['enStatus' => 'AwaitingPayment']);

        Notification::create([
            'user_id' => $apartmentuser['user_id'],
            'type'    => 'reservation_needs_payment',
            'data'    => [
                //     'title'        => __('booking.mark_reservation_notification_title'),
                'apartment_id' => $apartmentuser['apartment_id'],
                'Reservation'  => $apartmentuser
            ],
        ]);

        return response()->json([
            'message' => __('booking.mark_reservation_success')
        ], 200);
    }

    public function rejectReservation($BookingId)
    {
        $booking = Booking::where('id', $BookingId)->where('enStatus', 'Pending')->first();

        if (!$booking) {
            return response()->json([
                'message' => __('booking.reject_reservation_not_found')
            ], 404);
        }

        $owner = $this->getApartmentOwner($booking['apartment_id']);

        if (!$owner || $owner->id != Auth::id()) {
            return response()->json([
                'message' => __('booking.reject_reservation_unauthorized')
            ], 403);
        }

        $totalNights = $booking->endTerm->diffInDays($booking->startTerm, true) + 1;
        $totalPrice = $totalNights * $booking->priceAtBooking;
        $deposit = $this->calculateDeposit($totalPrice);


        //$renterCardNumbers = $this->getRenterCardsNumber($booking->apartment_id);
        $renterT = User::where('id',$booking->user_id)->first();
        $renterCardNumbers=$renterT->payments->pluck('cardNumber');
        

        $ownerCardsNumber = $this->getOwnerCardsNumber($booking->apartment_id);

        if (!($this->refundMoney($ownerCardsNumber, $renterCardNumbers, $deposit))) {
            return response()->json([
                'message' => __('booking.reject_reservation_refund_failed')
            ], 500);
        }

        $booking->update(['enStatus' => 'Cancelled']);


        Notification::create([
            'user_id' => $booking['user_id'],
            'type'    => 'reservation_rejected',
            'data'    => [
                'apartment_id' => $booking['apartment_id'],
                'Reservation'  => $booking
            ],
        ]);

        return response()->json([
            'message' => __('booking.reject_reservation_success')
        ], 200);
    }

    //renter
    public function showReservationsAwaitingPayment()
    {
        $booking = Booking::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->with(['apartment', 'user'])
            ->paginate(15);

        if ($booking->isEmpty()) {
            return response()->json([
                'message' => __('booking.show_reservations_awaiting_payment_empty')
            ], 404);
        }

        return response()->json([
            'message' => __('booking.show_reservations_awaiting_payment_success'),
            'data'    => BookingResource::collection($booking)
        ], 200);
    }


    //++ notiii
    public function userCancelReservation($BookingId)
    {
        $booking = Booking::find($BookingId);

        if (!$booking) {
            return response()->json([
                'message' => __('booking.user_cancel_reservation_not_found')
            ], 404);
        }

        $apartmentId = $booking->apartment_id;
        $apartmentOwner = $this->getApartmentOwner($apartmentId);

        $status = $booking->enStatus;

        if ($status === 'Pending' || $status === 'AwaitingPayment') {
            return $this->cancelPendingOrAwaitingPaymentReservation($apartmentOwner, $booking, $apartmentId);
        } elseif ($status === 'Accepted') {
            return $this->cancelAcceptedReservation($apartmentOwner, $booking, $apartmentId);
        } elseif ($status === 'Cancelled') {
            return response()->json([
                'message' => __('booking.user_cancel_reservation_already_cancelled')
            ], 200);
        }

        return response()->json([
            'message' => __('booking.user_cancel_reservation_unable')
        ], 400);
    }

    //helper 1// without band
    public function cancelPendingOrAwaitingPaymentReservation($apartmentOwner, $apartment_user, $apartmentId)
    {
        $totalNights = $apartment_user->endTerm->diffInDays($apartment_user->startTerm, true) + 1;
        $totalPrice = $totalNights * $apartment_user->priceAtBooking;
        $deposit = $this->calculateDeposit($totalPrice);

        $Payments = Auth::user()->payments;
        $cardNumbers = $Payments->pluck('cardNumber');

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);

        $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $deposit);

        if (!$FailRefund) {
            return response()->json([
                'message' => __('booking.cancel_pending_or_awaiting_refund_failed')
            ], 500);
        }

        $apartment_user->update(['enStatus' => 'Cancelled']);

        Notification::create([
            'user_id' => $apartment_user->user_id,
            'type'    => 'pending_reservation_canceled_renter',
            'data'    => [
                //   'title'        => __('booking.cancel_pending_or_awaiting_notification_renter', ['apartmentId' => $apartmentId]),
                'apartment_id' => $apartmentId,
            ],
        ]);

        Notification::create([
            'user_id' => $apartmentOwner->id,
            'type'    => 'pending_reservation_canceled_owner',
            'data'    => [
                //    'title'        => __('booking.cancel_pending_or_awaiting_notification_owner', ['apartmentId' => $apartmentId]),
                'apartment_id' => $apartmentId,
                'renter_id'    => $apartment_user->user_id,
            ],
        ]);

        return response()->json([
            'message' => __('booking.cancel_pending_or_awaiting_success')
        ], 200);
    }

    //helper 2 //within band
    public function cancelAcceptedReservation($apartmentOwner, $booking, $apartmentId)
    {
        $totalPrice = $this->TotalPriceReservation($booking->apartment_id, $booking->startTerm, $booking->endTerm, $booking);
        $remaining90 = $this->calculateRemainingAfterDeposit($totalPrice);

        $Payments = Auth::user()->payments;
        $cardNumbers = $Payments->pluck('cardNumber');

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartmentId);

        $now = Carbon::now();
        $start = Carbon::parse($booking->startTerm)->startOfDay();
        $end   = Carbon::parse($booking->endTerm)->endOfDay();
        $before3Days = $start->copy()->subDays(3);

        $FailRefund = false;
        $refundMessage = '';

        if ($now->between($start, $end, true)) {
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = __('booking.cancel_accepted_active_no_refund');
            $FailRefund = true;
        } elseif ($now->between($before3Days, $start, true)) {
            $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $remaining90);
            if (!$FailRefund) {
                return response()->json([
                    'message' => __('booking.cancel_accepted_refund_failed')
                ], 500);
            }
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = __('booking.cancel_accepted_within_3_days');
            $this->banUser(Auth::id(), "Canceled accepted reservation within last three days before start", 30);
        } elseif ($now->isBefore($before3Days)) {
            $FailRefund = $this->refundMoney($ownerCardsNumber, $cardNumbers, $remaining90);
            if (!$FailRefund) {
                return response()->json([
                    'message' => __('booking.cancel_accepted_refund_failed')
                ], 500);
            }
            $booking->update(['enStatus' => 'Cancelled']);
            $refundMessage = __('booking.cancel_accepted_in_advance');
        }

        Notification::create([
            'user_id' => $booking->user_id,
            'type'    => 'accepted_reservation_canceled_renter',
            'data'    => [
                //   'title'        => __('booking.cancel_accepted_notification_renter', ['apartmentId' => $apartmentId]),
                'apartment_id' => $apartmentId,
            ],
        ]);

        Notification::create([
            'user_id' => $apartmentOwner->id,
            'type'    => 'accepted_reservation_canceled_owner',
            'data'    => [
                //    'title'        => __('booking.cancel_accepted_notification_owner', ['apartmentId' => $apartmentId]),
                'apartment_id' => $apartmentId,
                'renter_id'    => $booking->user_id,
            ],
        ]);

        return response()->json([
            'message' => __('booking.cancel_accepted_success'),
            'details' => $refundMessage
        ], 200);
    }
    //renter
    ///$request->input('cardNumber')     // must do paymentRequest validation
    public function finalprocessPayment(PaymentRequest $request, $BookingId)
    {
        $validatedData = $request->validated();

        $booking = Booking::where('id', $BookingId)
            ->where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => __('booking.final_payment_reservation_not_found')
            ], 404);
        }

        $apartment = $booking->apartment;
        if (!$apartment) {
            return response()->json([
                'message' => __('booking.final_payment_apartment_not_found')
            ], 404);
        }

        $totalPrice = $this->TotalPriceReservation(
            $booking->apartment_id,
            $booking->startTerm,
            $booking->endTerm,
            $booking
        );

        if (!($this->cardStatus(
            $validatedData['cardNumber'],
            $this->calculateRemainingAfterDeposit($totalPrice),
            $validatedData['cvv']
        ))) {
            return response()->json([
                'message' => __('booking.final_payment_failed_owner_system'),
                'details' => __('booking.final_payment_card_invalid_or_insufficient')
            ], 402);
        }

        $ownerCardsNumber = $this->getOwnerCardsNumber($apartment->id);

        $isPaid = $this->payMoney(
            $ownerCardsNumber,
            $validatedData['cardNumber'],
            $this->calculateRemainingAfterDeposit($totalPrice)
        );

        if ($isPaid == false) {
            return response()->json([
                'message' => __('booking.final_payment_failed_owner_system'),
                'details' => __('booking.final_payment_failed_owner_system')
            ], 503);
        }

        Payment::create([
            'user_id'    => Auth::id(),
            'booking_id' => $booking->id,
            'amount'     => $this->calculateRemainingAfterDeposit($totalPrice),
            'cardNumber' => $validatedData['cardNumber'],
        ]);

        $booking->update(['enStatus' => 'Accepted']);

        Notification::create([
            'user_id' => Auth::id(),
            'type'    => 'payment_completed_renter',
            'data'    => [
                //    'title'        => __('booking.final_payment_notification_renter'),
                'apartment_id' => $apartment->id,
            ],
        ]);

        $owner = $this->getApartmentOwner($apartment->id);

        Notification::create([
            'user_id' => $owner->id,
            'type'    => 'payment_completed_owner',
            'data'    => [
                //   'title'        => __('booking.final_payment_notification_owner'),
                'apartment_id' => $apartment->id,
            ],
        ]);

        return response()->json([
            'message' => __('booking.final_payment_success')
        ], 200);
    }
    //////
    public function updateReservation(UpdateReservationRequest $request, $BookingId)
    {
        $validated = $request->validated();

        $booking = Booking::where('id', $BookingId)
            ->where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => __('booking.update_reservation_not_found')
            ], 404);
        }

        if (in_array($booking->enStatus, ['Cancelled', 'Accepted'])) {
            return response()->json([
                'message' => __('booking.update_reservation_forbidden_status')
            ], 403);
        }

        if (now()->gte(Carbon::parse($booking->startTerm))) {
            return response()->json([
                'message' => __('booking.update_reservation_already_started')
            ], 403);
        }

        if (
            Carbon::parse($booking->startTerm)->isSameDay($validated['startTerm']) &&
            Carbon::parse($booking->endTerm)->isSameDay($validated['endTerm'])
        ) {
            return response()->json([
                'message' => __('booking.update_reservation_no_changes')
            ], 422);
        }
        $apartment = $booking->apartment;


        if (!$this->checkAvailability(
            $validated['startTerm'],
            $validated['endTerm'],
            $apartment,
            $booking->id
        )) {
            return response()->json([
                'message' => __('booking.not_available_dates')
            ], 409);
        }


        $oldTotal   = $this->TotalPriceReservation(
            $booking->apartment_id,
            $booking->startTerm,
            $booking->endTerm,
            $booking
        );
        $oldDeposit = $this->calculateDeposit($oldTotal);


        $newTotal   = $this->TotalPriceReservation(
            $booking->apartment_id,
            $validated['startTerm'],
            $validated['endTerm'],
            $booking
        );
        $newDeposit = $this->calculateDeposit($newTotal);

        $difference = $newDeposit - $oldDeposit;

        $ownerCards   = $this->getOwnerCardsNumber($booking->apartment_id);
        $renterCards  = Auth::user()->payments()->pluck('cardNumber');

        $cardNumber = $validated['cardNumber'] ?? null;
        $cvv        = $validated['cvv'] ?? null;

        if ($difference > 0) {
            if (!$cardNumber || !$cvv) {
                return response()->json([
                    'message' => __('booking.update_reservation_card_required')
                ], 422);
            }
            if (!$this->cardStatus($cardNumber, $difference, $cvv)) {
                return response()->json([
                    'message' => __('booking.update_reservation_extra_payment_failed')
                ], 402);
            }

            if (!$this->payMoney($ownerCards, $cardNumber, $difference)) {
                return response()->json([
                    'message' => __('booking.update_reservation_extra_payment_failed')
                ], 503);
            }

            Payment::create([
                'user_id'    => Auth::id(),
                'booking_id' => $booking->id,
                'amount'     => $difference,
                'cardNumber' => $cardNumber,
            ]);
        }


        if ($difference < 0) {
            $refundAmountMoney = ($difference) * -1;

            if (!$this->refundMoney($ownerCards, $renterCards, $refundAmountMoney)) {
                return response()->json([
                    'message' => __('booking.update_reservation_refund_failed')
                ], 500);
            }
        }


        $booking->update([
            'startTerm' => $validated['startTerm'],
            'endTerm'   => $validated['endTerm'],
            'enStatus'  => 'Pending',
        ]);


        $owner = $this->getApartmentOwner($booking->apartment_id);
        Notification::create([
            'user_id' => $owner->id,
            'type'    => 'reservation_updated',
            'data'    => [
                'apartment_id' => $booking->apartment_id,
                'booking_id'   => $booking->id,
                'startTerm'    => $validated['startTerm'],
                'endTerm'      => $validated['endTerm'],
            ],
        ]);

        return response()->json([
            'message' => __('booking.update_reservation_success'),
            'data'    => new BookingResource($booking)
        ], 200);
    }





    ////////
}
