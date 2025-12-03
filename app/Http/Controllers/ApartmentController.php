<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FilterApartmentsRequest;
use App\Http\Requests\offerApartmentRequest;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\ApartmentUser;
use App\Models\Notification;
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
        $deposit = $this->calculateDeposit($apartment->price);
        if (!($this->cardStatus($request->input('cardNumber'), $deposit))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $this->depositPayment($request->input('cardNumber'), $apartment->price);

        $apartment->users()->syncWithoutDetaching([
            Auth::id() =>
            [
                'enType'    => 'Renter',
                'enStatus'  => 'Pending',
                'rate' => null,
                'startTerm' => $validated['startTerm'],
                'endTerm'   => $validated['endTerm'],
            ]
        ]);


        $owner = $this->getApartmentOwner($apartmentId);
        $owner_id = $owner->user_id;;

        Notification::create([
            'user_id' => $owner_id,
            'type'    => 'reservation_offer',
            'data'    => [
                'title'        => "New offer to your apartment",
                'apartment_id' => $apartmentId,
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
                'apartment_id' => $apartmentId,
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
        $apartmentuser = ApartmentUser::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')
            ->where('enStatus', 'Accepted')->first();

        if ($rate < 0 || $rate > 5) {
            return response()->json(['message' => 'please enter an active vlaue'], 203);
        }

        $mid_date = (Carbon::parse($apartmentuser['startTerm'])->startOfDay())->average(Carbon::parse($apartmentuser['endTerm'])->endOfDay());

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
    public function checkAvailability(string $start, string $end, Apartment $apartment)
    {
        $start_date = Carbon::parse($start)->startOfDay();
        $end_date   = Carbon::parse($end)->endOfDay();

        $apartment_user = ApartmentUser::where('enType', 'Renter')
            ->whereIn('enStatus', ['Accepted', 'Pending'])
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
        // $apartment = Apartment::where('id', '=', $apartmentId)->first();

        $reservationsOnApartment =  ApartmentUser::where('apartment_id', $apartmentId)
            ->orderBy('startTerm', 'asc')->get();
        return response()->json([
            'message' => null,
            'data' => $reservationsOnApartment
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
        for ($i = 0; $i < count($reservationsOnApartment); $i++) {
            $sum = $sum + $reservationsOnApartment[$i]->rate;
        }

        $avg = $sum / count($reservationsOnApartment);
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

        if ($request->has('minPrice') && $request->has('maxPrice')) {
            $query->whereBetween('price', [$request->minPrice, $request->maxPrice]);
        }

        if ($request->has('minArea') && $request->has('maxArea')) {
            $query->whereBetween('area', [$request->minArea, $request->maxArea]);
        }

        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->has('minRate') && $request->has('maxRate')) {
            $query->whereBetween('rate', [$request->minRate, $request->maxRate]);
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
    public function markReservationAwaitingPayment($userId, $apartmentId)
    {
        $apartment_user = ApartmentUser::where('user_id', $userId)
            ->where('apartment_id', $apartmentId)
            ->where('enStatus', 'Pending')
            ->first();

        if (!$apartment_user) {
            return response()->json(['message' => 'Approving failed'], 404);
        }

        $apartment_user->update(['enStatus' => 'AwaitingPayment']);

        Notification::create([
            'user_id' => $userId,
            'type'    => 'reservation_needs_payment',
            'data'    => [
                'title'        => "Your reservation has been approved. 
                Please complete the payment to finalize.",
                'apartment_id' => $apartmentId,
            ],
        ]);

        return response()->json(['message' => 'Approval done, awaiting payment'], 200);
    }
    //renter
    public function showReservationsAwaitingPayment()
    {
        $apartments = ApartmentUser::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->get();

        if ($apartments->isEmpty()) {
            return response()->json([
                'message' => 'There are no reservations awaiting payment'
            ], 404);
        }

        return response()->json([
            'message' => 'Reservations awaiting payment retrieved successfully',
            'apartments'    => $apartments
        ], 200);
    }
    //renter
    ///$request->input('cardNumber')     // must do paymentRequest validation
    public function processPayment(Request $request, $apartmentId)
    {
        $apartment_user = ApartmentUser::where('user_id', Auth::id())
            ->where('enType', 'Renter')
            ->where('enStatus', 'AwaitingPayment')
            ->where('apartmentId', $apartmentId)
            ->first();

        if (!$apartment_user) {
            return response()->json(['message' => 'Payment failed. Reservation not found'], 404);
        }
        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json(['message' => 'Payment failed. Apartment not found'], 404);
        }

        if (!($this->cardStatus($request->input('cardNumber'), $apartment->price))) {
            return response()->json([
                'message' => 'Payment failed',
                'details' => 'Either the card number is invalid or the card does not have sufficient funds'
            ], 402);
        }

        $this->completePayment($request->input('cardNumber'), $apartment->price);

        $apartment_user->update(['enStatus' => 'Accepted']);

        Notification::create([
            'user_id' => Auth::id(),
            'type'    => 'payment_completed',
            'data'    => [
                'title'        => "Payment completed successfully",
                'apartment_id' => $apartmentId,
            ],
        ]);
        $owner = $this->getApartmentOwner($apartmentId);
        Notification::create([
            'user_id' => $owner->user_id,
            'type'    => 'reservation_payment_received',
            'data'    => [
                'title'        => "The renter has completed the payment for your apartment",
                'apartment_id' => $apartmentId,
            ],
        ]);
        return response()->json([
            'message' => 'Payment completed successfully. You can now receive the apartment at any time.'
        ], 200);
    }
    //++ notiii
    public function updateResrevationStastus($apartmentId)
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
    }
    //helper 1
    public function cancelPendingReservation($owner, $apartmentId, $apartment_user)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => 'canceling faild'], 403);
        }

        $apartment_user->update(['enStatus' => 'Canceled']);

        Notification::create([
            'user_id' => $owner->id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "Reservation canceled on your apartment",
                'apartment_id' => $apartmentId,
                'renter_id'    => $apartment_user->user_id,
            ],
        ]);


        Notification::create([
            'user_id' => $apartment_user->user_id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "Your reservation has been canceled",
                'apartment_id' => $apartmentId,
            ],
        ]);

        return response()->json([
            'message' => 'Reservaion canceld successfully'
        ], 200);
    }
    //helper 2
    public function cancelAcceptedReservation($owner, $apartmentId, $apartment_user)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => 'canceling faild'], 403);
        }
        ///////////////////////////////////
        /**
         * to do code 
         */
        ////////////////////////////////////
        Notification::create([
            'user_id' => $owner->user_id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "Reservation canceled on your apartment",
                'apartment_id' => $apartmentId,
                'renter_id'    => $apartment_user->user_id,
            ],
        ]);


        Notification::create([
            'user_id' => $apartment_user->user_id,
            'type'    => 'reservation_canceled',
            'data'    => [
                'title'        => "Your reservation has been canceled",
                'apartment_id' => $apartmentId,
            ],
        ]);

        return response()->json([
            'message' => 'Reservaion canceld successfully'
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
    public function cardStatus($cardNumber, $apartmentPrice): bool
    {
        $json = Storage::get('cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as $card) {
            if (
                $cardNumber == $card['card_number'] &&
                Carbon::now()->lessThanOrEqualTo(Carbon::parse($card['expiry'])) &&
                $card['balance'] >= $apartmentPrice
            ) {
                return true;
            }
        }

        return false;
    }
    // $request->input('cardNumber') //helper
    public function completePayment($cardNumber, $apartmentPrice)
    {
        $json = Storage::get('cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['balance'] -= $apartmentPrice * 0.9;
                break;
            }
        }
        Storage::put('cards.json', json_encode($cards));
    }
    //helper
    public function depositPayment($cardNumber, $apartmentPrice)
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
    }
    //helper
    public function calculateDeposit($apartmentPrice)
    {
        return $apartmentPrice * 0.1;
    }
    //helper
    public function getApartmentOwner($apartmentId): ?ApartmentUser
    {
        return ApartmentUser::where('apartment_id', $apartmentId)
            ->where('enType', 'Owner')
            ->first();
    }

}
