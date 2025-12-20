<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FilterApartmentsRequest;
use App\Http\Requests\offerApartmentRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Http\Resources\ApartmentResource;
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
    public function store(StoreApartmentRequest $request)
    {
        $validated = $request->validated();
        $validated_data = [
            'enState' => $validated['enState'],
            'enCity'  => $validated['enCity'],
            'price'   => $validated['price'],
            'area'    => $validated['area'],
            'floor'   => $validated['floor'],
            'title'               => $validated['title'],
            'description'         => $validated['description'],
            'address_description' => $validated['address_description'],
        ];
        $validated_card = ['cardNumber' => $validated['cardNumber']];

        if (!($this->cardStatus($validated['cardNumber'], 0, 0, true))) {
            return response()->json([
                'message' => __('apartment.invalid_card'),
            ], 402);
        }
        $images_path = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('apartments', 'public');
                $images_path[] = $path;
            }
        }

        $validated_data['images'] = $images_path;

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
        Payment::create([
            'user_id' => Auth::id(),
            'booking_id'  => null,
            'amount' => 0.0,
            'cardNumber'  => $validated_card['cardNumber'],
        ]);
        return response()->json([
            'message' =>  __('apartment.created_successfully'),
            'data' => $apartment
        ], 201);
    }
    //owner
    public function update(UpdateApartmentRequest $request, $apartmentId)
    {
        $apartmentOwner = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => __('apartment.update_unauthorized'),
            ], 403);
        }

        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json([
                'message' => __('apartment.not_found')
            ], 404);
        }
        //
        $validated_data = $request->validated();

        $FinalImages = [];

        $OldImages = $apartment->images;
        if ($OldImages) {
            $FinalImages = $OldImages;
        }


        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $FinalImages[] = $img->store('apartments', 'public');
            }
        }




        if ($request->filled('delete_images')) {
            foreach ($request->delete_images as $img) {

                if (in_array($img, $FinalImages)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }
        if ($request->filled('delete_images')) {
            $FinalImages = array_values(array_diff(
                $FinalImages,
                $request->delete_images
            ));
        }



        $validated_data['images'] = $FinalImages;
        $apartment->update($validated_data);
        return response()->json([
            'message' => __('apartment.updated_successfully'),
            'data' => $apartment
        ], 200);
    }
    //owner
    public function delete($apartmentId, Request $request)
    {
        $delete_verified = $request->boolean('delete_verified');
        $apartmentOwner = Booking::where('user_id', Auth::id())
            ->where('apartment_id', $apartmentId)->where('enType', 'Owner')->first();
        if (!$apartmentOwner) {
            return response()->json([
                'message' => __('apartment.delete_unauthorized')
            ], 403);
        }
        $ban_count = Auth::user()->ban_count;
        if ((!$delete_verified) && (($this->ban_count_ondelete($apartmentId)) >= 1)) {
            return $this->warn_ondelete($ban_count, $this->ban_count_ondelete($apartmentId));
        }

        if (!$this->ConflictCheck($apartmentId)) {
            return response()->json([
                'message' => __('apartment.delete_has_active_reservation')
            ], 409);
        }
        $Accepted_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->where('enStatus', "Accepted")->orderBy('startTerm', 'asc')->get();
        $pending_AwaitingPayment_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->whereIn('enStatus', ["AwaitingPayment", "Pending"])->orderBy('startTerm', 'asc')->get();
        for ($i = 0; $i < count($pending_AwaitingPayment_offers); $i++) {
            $totalPrice = $this->TotalPriceReservation($pending_AwaitingPayment_offers[$i]->apartment_id, $pending_AwaitingPayment_offers[$i]->startTerm, $pending_AwaitingPayment_offers[$i]->endTerm, $pending_AwaitingPayment_offers[$i]);
            if ($totalPrice === null) {
                return response()->json([
                    'message' => __('apartment.delete_failed_invalid_reservations')
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
                continue;
            }

            $threshold = $start->copy()->subDays(3);

            if ($now->between($threshold, $start, true) && $now->lt($start)) {
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm, $offer);
                $this->cancelReservation($offer, $totalPrice);
                $bannedCheck = $this->banUser(Auth::id(), "cancel Accepted Reservation within last three_days before reservation", 60);
                Auth::user()->refresh();
                if (!$bannedCheck || Auth::user()->ban_count >= 3) {
                    return response()->json([
                        'message' => __('apartment.delete_failed_restrictions'),
                    ], 409);
                }
                continue;
            }
            if ($now->lt($threshold)) {
                $totalPrice = $this->TotalPriceReservation($offer->apartment_id, $offer->startTerm, $offer->endTerm, $offer);
                $this->cancelReservation($offer, $totalPrice);
                continue;
            }
        }
        $apartment = Apartment::find($apartmentId);
        if (!$apartment) {
            return response()->json([
                'message' => __('apartment.not_found')
            ], 404);
        }
foreach ($apartment->images ?? [] as $img) {
    Storage::disk('public')->delete($img);
}
        $apartment->delete();
        return response()->json([
            'message' => __('apartment.deleted_successfully')
        ], 200);
    }

    //renter
    public function showApartments()
    {
        $apartments = Apartment::paginate(15);
        if ($apartments->getCollection()->isEmpty()) {
            return response()->json([
                'message' => __('apartment.list_empty'),
                'data' => []
            ], 200);
        }
        return response()->json([
            'message' => __('apartment.list_retrieved'),
            'data' => ApartmentResource::collection($apartments)
        ], 200);
    }
    //renter
    public function showApartment($apartmentId)
    {
        $apartment = Apartment::where('id', $apartmentId)->first();
        if (!$apartment) {
            return response()->json([
                'message' => __('apartment.not_found'),
                'data' => null
            ], 404);
        }
        return response()->json(['message' => __('apartment.retrieved_successfully'), 'data' => $apartment], 200);
    }

    public function filterApartments(FilterApartmentsRequest $request)
    {
        $data = $request->validated();
        $query = Apartment::query();
        $checks = [
            ['minPrice', 'maxPrice'],
            ['minArea', 'maxArea'],
            ['minRate', 'maxRate'],
        ];
        foreach ($checks as [$min, $max]) {
            if ($request->filled($min) && $request->filled($max) && $request->$min > $request->$max) {
                return response()->json([
                    'message' => __('apartment.filter_invalid_range')
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

        $results = $query->get();
        return response()->json([
            'message' => $results->isEmpty()
                ? __('apartment.filter_no_results')
                : __('apartment.filter_success'),
            'data' => $results
        ]);
    }


    //renter
    public function addApartmentToFavoritesUser($apartmentId)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => __('apartment.not_found')], 404);
        }

        Auth::user()->favourites()->syncWithoutDetaching($apartmentId);

        return response()->json([
            'message' => __('apartment.favorite_added')
        ], 200);
    }
    //renter
    public function removeApartmentFromFavoritesUser($apartmentId)
    {
        $apartment = Apartment::find($apartmentId);

        if (!$apartment) {
            return response()->json(['message' => __('apartment.not_found')], 404);
        }

        if (!Auth::user()->favourites->contains($apartmentId)) {
            return response()->json(['message' => __('apartment.favorite_not_in_list')], 400);
        }

        Auth::user()->favourites()->detach($apartmentId);

        return response()->json([
            'message' => __('apartment.favorite_removed')
        ], 200);
    }
    //renter
    public function favourites()
    {
        $favourites = Auth::user()->favourites;

        if ($favourites->isEmpty()) {
            return response()->json([
                'message' => __('apartment.favorites_empty'),
            ], 200);
        }

        return response()->json([
            'message' => __('apartment.favorites_retrieved'),
            'favourites' => $favourites
        ], 200);
    }
}
