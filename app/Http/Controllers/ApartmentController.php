<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FilterApartmentsRequest;
use App\Http\Requests\offerApartmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\ApartmentUser;
use App\Models\Notification;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\CssSelector\Node\FunctionNode;

class ApartmentController extends Controller
{   //owner
    public function store(StoreApartmentRequest $request) //addApartment -> store becauseOf (RESTFUL API)
    {

        $validated = $request->validated();
        $apartment = Apartment::create($validated);
        $apartment->users()->syncWithoutDetaching([ //attach --> syncWithoutDetaching
            Auth::id() => [
                'enType'    => 'Owner',
                'enStatus'  => null,
                'rate'      => null,
                'startTerm' => null,
                'endTerm'   => null,
            ]
        ]);
        return response()->json([
            'message' => 'apartment added successfully ',
            'data' => $apartment
        ]);
    }
    //owner
    public function update(UpdateApartmentRequest $request, $apartmentId)
    {
        $apartmentOwner = ApartmentUser::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => 'apartment updated failed'
            ], 404);
        }

        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json([
                'message' => 'apartment updated failed'
            ], 404);
        }

        $apartment->update($request->validated());
        return response()->json([
            'message' => 'apartment updated successfully ',
            'data' => $apartment
        ], 200);
    }
    //owner
    public function delete($apartmentId)
    {
        $apartmentOwner = ApartmentUser::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => 'apartment deleted failed'
            ], 404);
        }
        if (!$this->ConflictCheck($apartmentId)) {
            return response()->json([
                'message' => 'You cannot delete this apartment because it has an active reservation.'
            ], 409);
        }
        $this->ConflictResolve($apartmentId);
        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json([
                'message' => 'apartment deleted failed'
            ], 404);
        }

        $apartment->delete();
        return response()->json([
            'message' => 'Apartment deleted successfully',
            'id' => $apartmentId
        ], 200);
    }
    //retner                                            //$request->input('cardNumber')
    public function offerApartment(offerApartmentRequest $request, int $apartmentId)
    {
        $validated = $request->validated();
        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment does not exist',
            ], 204);
        }

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
        $totalNights = $end->diffInDays($start) + 1;
        //تم زيادة واحد  لانه هاد التابع لا يحسب اليوم الأخير 
        $totalPrice = $totalNights * $apartment->price;
        $deposit = $this->calculateDeposit($totalPrice);
        if (!($this->cardStatus($validated['cardNumber'], $deposit, $validated['cvv']))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $this->completePayment($validated['cardNumber'], $deposit);
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
        $apartmentUser = ApartmentUser::create([
            'user_id' => Auth::id(),
            'apartment_id' => $apartment->id,
            'enType' => 'Renter',
            'enStatus' => 'Pending',
            'rate' => null,
            'startTerm' => $validated['startTerm'],
            'endTerm' => $validated['endTerm'],
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
        $owner_id = $owner->user_id;

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

    //التقييم لازم يكون rate + comment 
    public function EvaluateApartment(int $apartmentId, int $rate)
    {
        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => 'Apartment not found.'
            ], 404);
        }
        $apartmentuser = ApartmentUser::where('user_id', Auth::id())
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
        //تابع نحنا ساويناه بدل الموجود سابقا لانه هاد ادق وبالثواني هدلاك اذا مفرد مو زابط
        //$mid_date = (Carbon::parse($apartmentuser['startTerm'])->startOfDay())->average(Carbon::parse($apartmentuser['endTerm'])->endOfDay());

        if (!($mid_date->lt(now()))) {
            return response()->json(
                ['message' => 'Thank you for your support.
             You can leave a review for the apartment once at least half of the reservation period has passed.'],
                203
            );
        }

        $apartmentuser['rate'] = $rate;
        $apartmentuser->save();

        $apartmentOwner = $this->getApartmentOwner($apartmentId);
        $owner_id = $apartmentOwner['user_id'];

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
    //helper
    public function ConflictCheck($apartmentId)
    {
        $Accepted_offers = ApartmentUser::where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')->where('enStatus', "Accepted")->orderBy('startTerm', 'asc')->get();
        $now = Carbon::now();
        foreach ($Accepted_offers as $offer) {
            $start = Carbon::parse($offer->startTerm)->startOfDay();
            $end   = Carbon::parse($offer->endTerm)->endOfDay();
            if ($end->lt($now)) {
                continue;
            }
            if ($now->between($start, $end, true)) {
                //current
                return false;
            }
        }
        return true;
    }
    //helper
    public function ConflictResolve($apartmentId)
    {
        $Accepted_offers = ApartmentUser::where('apartment_id', $apartmentId)->where('enType', 'Renter')->where('enStatus', "Accepted")->orderBy('startTerm', 'asc')->get();
        $pending_AwaitingPayment_offers = ApartmentUser::where('apartment_id', $apartmentId)->where('enType', 'Renter')->whereIn('enStatus', ["AwaitingPayment", "Pending"])->orderBy('startTerm', 'asc')->get();
        for ($i = 0; $i < count($pending_AwaitingPayment_offers); $i++) {
            $totalPrice = $this->TotalPriceReservation($pending_AwaitingPayment_offers[$i]->apartment_id, $pending_AwaitingPayment_offers[$i]->startTerm, $pending_AwaitingPayment_offers[$i]->endTerm);
            if ($totalPrice === null) {
                return response()->json([
                    'message' => 'Invalid reservation period or apartment not found.'
                ], 422);
            }
            $deposit = $this->calculateDeposit($totalPrice);
            $this->cancelReservation($pending_AwaitingPayment_offers[$i], $deposit);
        }

        $now = Carbon::now();
        foreach ($Accepted_offers as $offer) {
            $start = Carbon::parse($offer->startTerm)->startOfDay();
            $end   = Carbon::parse($offer->endTerm)->endOfDay();

            if ($end->lt($now)) {
                //past
                continue;
            }

            $threshold = $start->copy()->subDays(3); //ساوينا له نسخ مشان ما يغير المتغير الأصلي start

            if ($now->between($threshold, $start, true) && $now->lt($start)) {
                //within_three_days
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm);
                $this->cancelReservation($offer, $totalPrice);
                $bannedCheck = $this->banUser(Auth::id(), "cancel Accepted Reservation within last three_days before reservation", 60);
                if (!$bannedCheck || Auth::user()->ban_type === 'Permanent') {
                    return response()->json([
                        'message' => 'The cancellation process could not be completed due to errors.',
                    ]);
                }
                continue;
            }

            /*if ($now->between($start, $end, true)) {
            //current
           
            continue;
        }*/

            if ($now->lt($threshold)) {
                //future
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm);
                $this->cancelReservation($offer, $totalPrice);
                continue;
            }

            //future
        }
    }
    //helper 
    public function banUser($userId, string $reason, int $days)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        if ($user->ban_count == 2) {
            $user->update([
                'isbanned'    => true,
                'banned_until' => null,
                'ban_type'    => 'Permanent',
                'ban_count'    => 3,
            ]);
        } else {
            $user->update([
                'isbanned'    => true,
                'banned_until' => now()->addDays($days),
                'ban_type'    => 'Temporary',
                'ban_count'    => $user->ban_count + 1,
            ]);
        }


        $user->ban_reasons_history = array_merge((array)$user->ban_reasons_history, [
            [
                'banned_number' => $user->ban_count,
                'reason' => $reason,
            ]
        ]);
        $user->save();

        return true;
    }
    //helper
    public function checkAvailability(string $start, string $end, Apartment $apartment)
    {
        $start_date = Carbon::parse($start)->startOfDay();
        $end_date   = Carbon::parse($end)->endOfDay();

        $apartment_user = ApartmentUser::where('enType', 'Renter')
            ->whereIn('enStatus', ['Accepted', 'Pending', 'AwaitingPayment'])
            ->where('apartment_id', '=', $apartment->id)->orderBy('startTerm', 'asc')->get();

        if ($apartment_user->isEmpty()) {
            return true;
        }

        $start_date_of_first_reserve = Carbon::parse($apartment_user->first()->startTerm)->startOfDay();
        if ($end_date->lt($start_date_of_first_reserve)) {
            return true;
        }

        $end_date_of_last_reserve = Carbon::parse($apartment_user->last()->endTerm)->endOfDay();

        if ($start_date->gt($end_date_of_last_reserve)) {
            return true;
        }

        foreach ($apartment_user as $index => $current) {
            $next = $apartment_user->get($index + 1);

            if (! $next) {
                break;
            }

            $currentEnd = Carbon::parse($current->endTerm)->endOfDay();
            $nextStart  = Carbon::parse($next->startTerm)->startOfDay();

            if ($start_date->gt($currentEnd) && $end_date->lt($nextStart)) {
                return true;
            }
        }


        return false;
    }

    // for the owner
    public function Show_Reservations(int $apartmentId)
    {
        $apartment = Apartment::where('id', '=', $apartmentId)->first();
        if(!$apartment){
       return response()->json([
            'message' => "apartment not found "
        ]);
        }
        $reservationsOnApartment =  ApartmentUser::where('apartment_id', $apartmentId)
            ->orderBy('startTerm', 'asc')->get();
            if($reservationsOnApartment->isEmpty()){
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
        
        $Owned_apartments = ApartmentUser::where('user_id', Auth::id())->where('enType', 'Owner');
        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');
        $reservationsOnApartments = ApartmentUser::whereIn('apartment_id', $Owned_apartments_Ids)
            ->orderBy('startTerm', 'asc')->get();
        return response()->json([
            'message' => null,
            'data' => $reservationsOnApartments
        ]);
    }
    //owner
    public function ShowAllPendingReservations()
    {
        $Owned_apartments = ApartmentUser::where('user_id', Auth::id())->where('enType', 'Owner')->get();
        $Owned_apartments_Ids = $Owned_apartments->pluck('apartment_id');
        $reservationsOnApartments = ApartmentUser::whereIN('apartment_id', $Owned_apartments_Ids)->where('enStatus', 'Pending')->where('enType', 'Renter')->orderBy('startTerm', 'asc')->get();
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
        $PendingReservations = ApartmentUser::where('id', $ApartmentUserID)->first();
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
    //renter
    public function showApartments()
    {
        $apartments = Apartment::paginate(15);
        if ($apartments->getCollection()->isEmpty()) {
            return response()->json([
                'mes' => 'the apartments-list in this page is empty.',
                'data' => null
            ], 404);
        }
        $apartments->getCollection()->transform(function ($apartment) {
            $apartment->rate = $this->totalRateAccount($apartment->id); // انتبه: totalRateAccount يجب أن يرجع قيمة
            return $apartment;
        });
        return response()->json(['mes' => null, 'data' => $apartments]);
    }
    //renter
    public function showApartment($apartmentId)
    {
        $apartment = Apartment::where('id', $apartmentId)->first();
        $apartment['rate'] = $this->totalRateAccount($apartmentId);
        return response()->json(['message' => null, 'data' => $apartment]);
    }
    //helper
    public function totalRateAccount(int $apartmentId)
    {

        $reservationsOnApartment = ApartmentUser::where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')->get();

        if (count($reservationsOnApartment) == 0) {
            return 0;
        }

        $sum = 0;
        $numbersOfRates = 0; //بدنا بس يلي مقيمين ما بدنا الnull يلي لسى مو مقيمين ينحسبوا 
        for ($i = 0; $i < count($reservationsOnApartment); $i++) {
            if ($reservationsOnApartment[$i]->rate === null) {
                continue;
            }
            $numbersOfRates++;
            $sum = $sum + $reservationsOnApartment[$i]->rate;
        }
        if ($numbersOfRates == 0) {
            return 0;
        }
        $avg = $sum / $numbersOfRates;
        return $avg;
    }
    //renter
    public function filterApartments(FilterApartmentsRequest $request)
    {
        $query = Apartment::query();

        if ($request->has('enCity')) {
            $query->where('enCity', $request->enCity);
        }

        if ($request->has('enState')) {
            $query->where('enState', $request->enState);
        }

        if ($request->has('minPrice')) {
            $query->where('price', '>=', $request->minPrice);
        }

        if ($request->has('maxPrice')) {
            $query->where('price', '<=', $request->maxPrice);
        }

        if ($request->has('minArea')) {
            $query->where('area', '>=', $request->minArea);
        }
        if ($request->has('maxArea')) {
            $query->where('area', '<=', $request->maxArea);
        }

        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->has('minRate')) {
            $query->where('rate', '>=', $request->minRate);
        }

        if ($request->has('maxRate')) {
            $query->where('rate', '<=', $request->maxRate);
        }

        if ($request->has('order')) {
            if ($request->input('order') == 'asc') {
                $query->orderBy('price', 'asc');
            } else {
                $query->orderBy('price', 'desc');
            }
        }

        $filterApartments = $query->get();

        return response()->json([
            'message' => 'Filtered apartments',
            'data' => $filterApartments
        ], 200);
    }
    //owner   approveReservation
    public function markReservationAwaitingPayment($ApartmentUserID)
    {
        $apartmentuser = ApartmentUser::where('id', $ApartmentUserID)->first();
        if (!$apartmentuser) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
        $owner = $this->getApartmentOwner($apartmentuser['apartment_id']);
        if (!$owner || $owner['user_id'] != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!$apartmentuser) {
            return response()->json(['message' => 'Approving failed'], 404);
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
        $apartmentuser = ApartmentUser::where('user_id', Auth::id())
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
    //renter
    ///$request->input('cardNumber')     // must do paymentRequest validation
    public function finalprocessPayment(PaymentRequest $request, $ApartmentUserID)
    {
        $validatedData = $request->validated();
        $apartment_user = ApartmentUser::where('id', $ApartmentUserID)
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
        $totalPrice = $this->TotalPriceReservation($apartment_user->apartment_id, $apartment_user->startTerm, $apartment_user->endTerm);

        if (!($this->cardStatus($validatedData['cardNumber'], $totalPrice * 0.9, $validatedData['cvv']))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $this->completePayment($validatedData['cardNumber'], $totalPrice * 0.9);

        Payment::create([
            'user_id' => Auth::id(),
            'booking_id'  => $apartment_user->id,
            'amount' => $totalPrice * 0.9,
            'cardnumber'  => $validatedData['cardNumber'],
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
    //++ notiii
    //ما فهمت شو الغاية منها 
    /* public function updateResrevationStastus($apartmentId)
    {
        //first of all we need to fetch the owner of the apartment becauseof notification
        $apartmentOwner = $this->getApartmentOwner($apartmentId);
        //fetch the relatoinship
        $apartment_user = ApartmentUser::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')->first();

        if (!$apartment_user) {
            return response()->json(['message' => 'canceling faild'], 403);
        }

        $status = $apartment_user->enStatus;

        if ($status === 'Pending') {
            $this->cancelPendingReservation($apartmentOwner, $apartmentId, $apartment_user);
        } elseif ($status === 'Accepted') {
            $this->cancelAcceptedReservation($apartmentOwner, $apartmentId, $apartment_user);
        } elseif ($status === 'Cancled') {
        }
        ///////////////////////////////////////////////////
    }*/
    //helper 
    public function TotalPriceReservation($apartment_id, $start, $end)
    {
        // $apartment_id = $apartment_user['apartment_id'];
        $apartment = Apartment::where('id', $apartment_id)->first();
        if (!$apartment) {
            return null;
        }
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        if ($end->lt($start)) {
            return null;
        }
        $totalNights = $end->diffInDays($start) + 1;
        //تم زيادة واحد  لانه هاد التابع لا يحسب اليوم الأخير 
        $totalPrice = $totalNights * $apartment->price;
        return $totalPrice;
    }
    public function cancelReservation($apartment_user, $Amount)
    {

        $Payments = $apartment_user->payments();
        $cardNumbers = $Payments->pluck('cardNumber'); //هاد التابع بجيب كل ارقام البطاقات بالpayments 
        $FailRefund = false;
        for ($i = 0; $i < count($cardNumbers); $i++) {
            if ($this->cardStatus($cardNumbers[$i], 0, 0, true)) {
                $FailRefund = true;
                $this->completePayment($cardNumbers[$i], -1 * $Amount);
                break;
            }
        }
        if (!$FailRefund) {
            //نحط اسمه بملف جيسون بحيث تابع اخر يقدر يستعيدهم منه 
        }

        $apartment_user->update(['enStatus' => 'Cancelled']);

        Notification::create([
            'user_id' => $apartment_user['user_id'],
            'type'    => 'reservation_cancelled',
            'data'    => [
                'title'        => "Reservation cancelled on your apartment and your paid amount has been refunded ,
                if you experience any problem in refunding money - pleas check refund_money tab",
                'apartment_id' => $apartment_user['apartment_id']
            ],
        ]);

        return response()->json([
            'message' => 'Reservaion cancelld successfully'
        ], 200);
    }

    //renter
    public function addApartmentToFavoritesUser($apartmentId)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        Auth::user()->favourites()->syncWithoutDetaching($apartmentId);

        return response()->json([
            'message' => 'Apartment added to favorites successfully'
        ], 200);
    }
    //renter
    public function removeApartmentFromFavoritesUser($apartmentId)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        if (!Auth::user()->favourites->contains($apartmentId)) {
            return response()->json(['message' => 'Apartment is not in your favorites'], 400);
        }

        Auth::user()->favourites()->detach($apartmentId);

        return response()->json([
            'message' => 'Apartment removed from favorites successfully'
        ], 200);
    }
    //renter
    public function favourites()
    {
        $favourites = Auth::user()->favourites;

        if ($favourites->isEmpty()) {
            return response()->json([
                'message' => 'You have no apartments in your favorites list.',
            ], 200);
        }

        return response()->json([
            'message' => 'Here are your favorite apartments.',
            'favourites' => $favourites
        ], 200);
    }
    public function cardStatus($cardNumber, $Amount, $cvv, $checkCvv = false): bool
    {
        // تأكد من وجود ملف البطاقات
        $path = 'private/cards.json';
        if (!Storage::exists($path)) {
            return false;
        }
        $json = Storage::get('private/cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as $card) {
            if (
                $cardNumber == $card['card_number'] &&
                ($cvv == $card['cvv'] || $checkCvv) &&
                Carbon::now()->lessThanOrEqualTo(Carbon::parse($card['expiry'])) &&
                $card['balance'] >= $Amount
            ) {
                return true;
            }
        }

        return false;
    }
    // $request->input('cardNumber') //helper
    public function completePayment($cardNumber, $Amount)
    {
        $json = Storage::get('private/cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['balance'] -= $Amount;
                break;
            }
        }
        Storage::put('private/cards.json', json_encode($cards));
    }
    //ما في داعي تابع تاني منقدر نستخدم التابع يلي فوق بس منعكس اشارة الamount 
    /*  public function refundAmount($cardNumber ,$Amount){
 $json = Storage::get('cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['balance'] += $Amount;
                break;
            }
        }
        Storage::put('cards.json', json_encode($cards));
    }*/
    //helper
    /* public function depositPayment($cardNumber, $apartmentPrice)
    {
        $json = Storage::get('cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['balance'] -= $this->calculateDeposit($apartmentPrice);
                break;
            }
        }
        Storage::put('cards.json', json_encode($cards));
    }*/
    //helper
    public function calculateDeposit($Amount)
    {
        return $Amount * 0.1;
    }
    //helper
    public function calculateRemainingAfterDeposit($Amount)
    {
        return $Amount * 0.9;
    }

    //helper
    public function getApartmentOwner($apartmentId): ?ApartmentUser
    {
        return ApartmentUser::where('apartment_id', $apartmentId)
            ->where('enType', 'Owner')
            ->first();
    }
    //helper 
    public function midDate($start, $end): Carbon
    {
        $start = Carbon::parse($start)->startOfDay();
        $end   = Carbon::parse($end)->endOfDay();

        $TermInSeconds = $end->getTimestamp() - $start->getTimestamp();
        $halfSeconds = (int) floor($TermInSeconds / 2);

        return $start->copy()->addSeconds($halfSeconds);
    }
}
