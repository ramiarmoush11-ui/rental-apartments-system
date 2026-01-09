<?php

namespace App\Traits;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

trait BookingLogicTrait
{
    //helper
    public function ConflictCheck($apartmentId)
    {
        $Accepted_offers = Booking::where('apartment_id', $apartmentId)
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

    public function cancelReservation($booking, $Amount)
    {
        $Payments = User::where('id', $booking->user_id)->first()->payments;

        $userCardsNumber = $Payments->pluck('cardNumber');
        $ownerCardsNumber = $this->getOwnerCardsNumber($booking->apartment_id);

        $FailRefund = $this->refundMoney($ownerCardsNumber, $userCardsNumber, $Amount);

        if (!$FailRefund) {
            return response()->json([
                'message' => __('bookingTrait.cancel_reservation_refund_failed')
            ], 500);
        }

        $booking->update(['enStatus' => 'Cancelled']);

        Notification::create([
            'user_id' => $booking['user_id'],
            'type'    => 'reservation_cancelled',
            'data'    => [
                'title'        => __('bookingTrait.cancel_reservation_notification_renter'),
                'apartment_id' => $booking['apartment_id']
            ],
        ]);

        return response()->json([
            'message' => __('bookingTrait.cancel_reservation_success')
        ], 200);
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
    public function checkAvailability(string $start, string $end, Apartment $apartment): bool
    {
        $start_date = Carbon::parse($start)->startOfDay();
        $end_date   = Carbon::parse($end)->endOfDay();

        $bookings = Booking::where('apartment_id', $apartment->id)
            ->whereIn('enStatus', ['Accepted', 'Pending', 'AwaitingPayment'])
            ->orderBy('startTerm', 'asc')
            ->get();

        if ($bookings->isEmpty()) {
            return true;
        }


        $start_date_of_first_reserve = Carbon::parse($bookings->first()->startTerm)->startOfDay();
        $end_date_of_last_reserve = Carbon::parse($bookings->last()->endTerm)->endOfDay();

        if ($end_date->lt($start_date_of_first_reserve)) {
            return true;
        }

        if ($start_date->gt($end_date_of_last_reserve)) {
            return true;
        }

        for ($i = 0; $i < $bookings->count() - 1; $i++) {
            $currentEnd = Carbon::parse($bookings[$i]->endTerm)->endOfDay();
            $nextStart  = Carbon::parse($bookings[$i + 1]->startTerm)->startOfDay();

            if ($start_date->gt($currentEnd) && $end_date->lt($nextStart)) {
                return true;
            }
        }

        return false;
    }

    //helper
    public function totalRateAccount(int $apartmentId)
    {

        $reservationsOnApartment = Booking::where('apartment_id', $apartmentId)
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

    //helper 
    public function midDate($start, $end): Carbon
    {
        $start = Carbon::parse($start)->startOfDay();
        $end   = Carbon::parse($end)->endOfDay();

        $TermInSeconds = $end->getTimestamp() - $start->getTimestamp();
        $halfSeconds = (int) floor($TermInSeconds / 2);

        return $start->copy()->addSeconds($halfSeconds);
    }

    //helper
    public function getApartmentOwner($apartmentId): ?User
    {
        $ownerBooking = Booking::where('apartment_id', $apartmentId)
            ->where('enType', 'Owner')
            ->first();

        if (!$ownerBooking) {
            return null;
        }

        return $ownerBooking->user;
    }

    public function getOwnerCardsNumber($apartmentId) //: ?string لا تعمل هيك مهما كلف الثمن 
    {
        $owner = $this->getApartmentOwner($apartmentId);

        if (!$owner) {
            return null;
        }
        $ownerPayments = $owner->payments;

        $ownerCardsNumber = $ownerPayments->pluck('cardNumber')->toArray();
        return $ownerCardsNumber;
    }


    //helper
    public function getApartmentRenter($apartmentId): ?User
    {
        $renterBooking = Booking::where('apartment_id', $apartmentId)
            ->where('enType', 'Renter')
            ->first();

        if (!$renterBooking) {
            return null;
        }

        return $renterBooking->user;
    }

    public function getRenterCardsNumber($apartmentId) //: ?string لا تعمل هيك مهما كلف الثمن 
    {
        $renter = $this->getApartmentRenter($apartmentId);

        if (!$renter) {
            return null;
        }
        $renterPayments = $renter->payments;

        $renterCardsNumber = $renterPayments->pluck('cardNumber')->toArray();
        return $renterCardsNumber;
    }


    public function warn_ondelete($ban_count, $ban_count_ondelete)
    {
        return response()->json([
            'message' => __('bookingTrait.warn_ondelete_message'),
            'ban_count_before' => $ban_count,
            'ban_count_ondelete' => $ban_count_ondelete,
            'note' => __('bookingTrait.warn_ondelete_note')
        ], 200);
    }

    public function ban_count_ondelete($apartmentId)
    {
        $ban_count = 0;
        $Accepted_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->where('enStatus', "Accepted")->orderBy('startTerm', 'asc')->get();
        $pending_AwaitingPayment_offers = Booking::where('apartment_id', $apartmentId)->where('enType', 'Renter')->whereIn('enStatus', ["AwaitingPayment", "Pending"])->orderBy('startTerm', 'asc')->get();

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
                $ban_count++;
                continue;
            }

            /*if ($now->between($start, $end, true)) {
                //current
            
                continue;
            }*/

            if ($now->lt($threshold)) {
                //future
                continue;
            }

            //future
        }
        return $ban_count;
    }
}
