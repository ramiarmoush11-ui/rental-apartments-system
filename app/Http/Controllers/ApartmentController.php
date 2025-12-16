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
        $delete_verified = $request->boolean('delete_verified'); //اذا كان مو مبعوت فلح تعتبر false bec it is null 
        $apartmentOwner = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => 'apartment deleted failed'
            ], 404);
        }
        //// التحذير يلي حيكنا عليه .....هيك ارجل حل بدون تعقيد
        $ban_count = Auth::user()->ban_count;
        if ((!$delete_verified) && (($this->ban_count_ondelete($apartmentId) + $ban_count) >= 3)) {
            $this->warn_ondelete($ban_count);
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
        /*   $apartments->getCollection()->transform(function ($apartment) {
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

    //renter
    public function filterApartments(FilterApartmentsRequest $request)
    {
        $query = Apartment::query();

        if ($request->filled('enCity')) {
            $query->where('enCity', $request->enCity);
        }

        if ($request->filled('enState')) {
            $query->where('enState', $request->enState);
        }

        if ($request->filled('minPrice')) {
            $query->where('price', '>=', $request->minPrice);
        }

        if ($request->filled('maxPrice')) {
            $query->where('price', '<=', $request->maxPrice);
        }

        if ($request->filled('minArea')) {
            $query->where('area', '>=', $request->minArea);
        }
        if ($request->filled('maxArea')) {
            $query->where('area', '<=', $request->maxArea);
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->filled('minRate')) {
            $query->where('rate', '>=', $request->minRate);
        }

        if ($request->filled('maxRate')) {
            $query->where('rate', '<=', $request->maxRate);
        }

        if ($request->filled('order')) {
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
