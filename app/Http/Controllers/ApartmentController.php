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
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\CssSelector\Node\FunctionNode;
use App\Traits\PaymentProcessingTrait;
use App\Traits\BookingLogicTrait;

class ApartmentController extends Controller
{
    use PaymentProcessingTrait, BookingLogicTrait;

    //owner
    public function store(StoreApartmentRequest $request) //addApartment -> store becauseOf (RESTFUL API)
    {

     
        $validated = $request->validated();
        $validated_data = [
            'enState' => $validated['enState'],
            'enCity'  => $validated['enCity'],
            'price'   => $validated['price'],
            'area'    => $validated['area'],
            'floor'   => $validated['floor'],
        ];
        $validated_card = ['cardNumber' => $validated['cardNumber']];
        
         if (!($this->cardStatus($validated['cardNumber'], 0, 0,true))) {
            return response()->json([
                'message' => 'invalid card number',
            ], 402);
        }

        $apartment = Apartment::create($validated_data);
        Booking::create(
            [
                'user_id' => Auth::id(),
                'apartment_id' => $apartment->id,
                'enType'    => 'Owner',
                'enStatus'  => null,
                'rate'      => null,
                'startTerm' => null,
                'endTerm'   => null,
                'priceAtBooking' => $validated['price']
            ]
        );

        //هون انا عم هيئ كرت واحد عالقليلة 
        Payment::create([
            'user_id' => Auth::id(),
            'booking_id'  => null,
            'amount' => 0.0,
            'cardNumber'  => $validated_card['cardNumber'],
        ]);
        return response()->json([
            'message' => 'apartment added successfully ',
            'data' => $apartment
        ]);
    }
    //owner
    public function update(UpdateApartmentRequest $request, $apartmentId)
    {
//echo($request->all());
         //dd($request->all());
        $apartmentOwner = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => 'apartment updated failed (poss)'
            ], 404);
        }

        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json([
                'message' => 'apartment updated failed'
            ], 404);
        }

//echo($request->validated());
        $apartment->update($request->validated());
        $apartment->save();
        return response()->json([
            'message' => 'apartment updated successfully ',
            'data' => $apartment
        ], 200);
    }
    //owner
    public function delete($apartmentId, Request $request)
    {
          echo("9999999999999999");
                echo(Auth::user()->ban_count);
        echo("apartmentId");
        echo($apartmentId);
        $delete_verified = $request->boolean('delete_verified'); //اذا كان مو مبعوت فلح تعتبر false bec it is null 
        echo('delete_verified');
        echo($delete_verified);
        $apartmentOwner = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => 'apartment deleted failed'
            ], 404);
        }
        //// التحذير يلي حيكنا عليه .....هيك ارجل حل بدون تعقيد
        $ban_count = Auth::user()->ban_count;
        echo("heeeeerrrrreeee");
        echo($this->ban_count_ondelete($apartmentId));//2
        echo((($this->ban_count_ondelete($apartmentId)) >= 1));//1
        if ((!$delete_verified) && (($this->ban_count_ondelete($apartmentId)) >= 1)) {
            echo("warrrrrrnnnnnnnnnnnnnnnnnnnnnnnnnnn");
            return $this->warn_ondelete($ban_count,$this->ban_count_ondelete($apartmentId));
        }

        if (!$this->ConflictCheck($apartmentId)) {
            return response()->json([
                'message' => 'You cannot delete this apartment because it has an active reservation.'
            ], 409);
        }
        //return $this->ConflictResolve($apartmentId);
        //
$Accepted_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->where('enStatus', "Accepted")->orderBy('startTerm', 'asc')->get();
        $pending_AwaitingPayment_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->whereIn('enStatus', ["AwaitingPayment", "Pending"])->orderBy('startTerm', 'asc')->get();
        for ($i = 0; $i < count($pending_AwaitingPayment_offers); $i++) {
            $totalPrice = $this->TotalPriceReservation($pending_AwaitingPayment_offers[$i]->apartment_id, $pending_AwaitingPayment_offers[$i]->startTerm, $pending_AwaitingPayment_offers[$i]->endTerm, $pending_AwaitingPayment_offers[$i]);
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
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm, $offer);
                $this->cancelReservation($offer, $totalPrice);
                $bannedCheck = $this->banUser(Auth::id(), "cancel Accepted Reservation within last three_days before reservation", 60);
              //ban_count
              Auth::user()->refresh();
                if (!$bannedCheck || Auth::user()->ban_count >=3) {
                echo("88888888888888888");
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
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm,$offer);
                $this->cancelReservation($offer, $totalPrice);
                continue;
            }

            //future
        }




        //
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
        /*  $apartments->getCollection()->transform(function ($apartment) {
            $apartment->rate = $this->totalRateAccount($apartment->id); // انتبه: totalRateAccount يجب أن يرجع قيمة
            return $apartment;
        });*/
        return response()->json(['mes' => null, 'data' => $apartments],200);
    }
    //renter
    public function showApartment($apartmentId)
    {
        $apartment = Apartment::where('id', $apartmentId)->first();
        //$apartment['rate'] = $this->totalRateAccount($apartmentId);
        return response()->json(['message' => null, 'data' => $apartment]);
    }

  public function filterApartments(FilterApartmentsRequest $request)
{
    $data = $request->validated();
    $query = Apartment::query();
//ليش ما حطيناها بال validation rules) 
//لانه وقتها بدنا ننجبر نحط التانية 
//متل الstate + city (مدينة لحالها ما بتنفع )
$checks = [
    ['minPrice', 'maxPrice'],
    ['minArea', 'maxArea'],
    ['minRate', 'maxRate'],
];
foreach ($checks as [$min, $max]) {
    if ($request->filled($min) && $request->filled($max) && $request->$min > $request->$max) {
        return response()->json([
            'message' => "it cant be the min value bigger than the min value "
        ], 422);
    }
}

    if (!empty($data['enCity'])) {
        $query->where('enCity', $data['enCity']);
    }

    if (!empty($data['enState'])) {
        $query->where('enState', $data['enState']);
    }

    if (!empty($data['minPrice'])) {
        $query->where('price', '>=', $data['minPrice']);
    }

    if (!empty($data['maxPrice'])) {
        $query->where('price', '<=', $data['maxPrice']);
    }

    if (!empty($data['minArea'])) {
        $query->where('area', '>=', $data['minArea']);
    }

    if (!empty($data['maxArea'])) {
        $query->where('area', '<=', $data['maxArea']);
    }

    if (!empty($data['floor'])) {
        $query->where('floor', $data['floor']);
    }

    if (!empty($data['minRate'])) {
        $query->where('rate', '>=', $data['minRate']);
    }

    if (!empty($data['maxRate'])) {
        $query->where('rate', '<=', $data['maxRate']);
    }

    if (!empty($data['order'])) {
        $query->orderBy('price', $data['order'] === 'asc' ? 'asc' : 'desc');
    }

    return response()->json([
        'message' => 'Filtered apartments',
        'data' => $query->get()
    ]);
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

    /**
     * *filter + 
     * list +
     *  addappartment+updateapartment +
     * show alapartment
     * *هدول يلي جربتهم */
}
