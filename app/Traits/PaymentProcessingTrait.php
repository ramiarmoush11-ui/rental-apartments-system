<?php

namespace App\Traits;

use App\Http\Requests\PaymentRequest;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

trait PaymentProcessingTrait
{
    public function refundMoney($ownerCardsNumber, $userCardNumbers, $amount): bool
    {
        $Refund1 = false;
        $Refund2 = false;
        $validated_card1 = null;
        $validated_card2 = null;
        for ($i = 0; $i < count($ownerCardsNumber); $i++) {
            if ($this->cardStatus($ownerCardsNumber[$i], $amount, 0, true)) {
                $Refund2 = true;
                $validated_card2 = $ownerCardsNumber[$i];
                break;
            }
        }
        if (!$Refund2) {
            return false;
        }

        for ($i = 0; $i < count($userCardNumbers); $i++) {
            if ($this->cardStatus($userCardNumbers[$i], 0, 0, true)) {
                $Refund1 = true;
                $validated_card1 = $userCardNumbers[$i];
                break;
            }
        }

        if ($Refund2 && $Refund1) {
            $this->completePayment($validated_card1, -1 * $amount);
            $this->completePayment($validated_card2, $amount);
            return true;
        }
        return false;
    }
    public function payMoney($ownerCardsNumber, $userCardNumber, $amount): bool
    {

        $Paid = false;
        for ($i = 0; $i < count($ownerCardsNumber); $i++) {
            if ($this->cardStatus($ownerCardsNumber[$i], 0, 0, true)) {
                $Paid = true;
                $this->completePayment($ownerCardsNumber[$i], -1 * $amount);
                $this->completePayment($userCardNumber, $amount);
                break;
            }
        }
        return $Paid;
    }

    //helper
    public function calculateRemainingAfterDeposit($Amount)
    {
        return $Amount * 0.9;
    }

    public function calculateDeposit($Amount): float
    {
        return $Amount * 0.1;
    }

    // $request->input('cardNumber') //helper
    public function completePayment($cardNumber, $amount): bool
    {
        $path = storage_path('app/private/cards.json');

        if (!file_exists($path)) {
            return false;
        }

        $json = file_get_contents($path);
        $cards = json_decode($json, true);

        if (is_null($cards)) {
            return false;
        }

        foreach ($cards as &$card) {
            $plainCardNumber = Crypt::decryptString($card['card_number']);
            if ($cardNumber == $plainCardNumber) {
                $card['balance'] -= $amount;
                break;
            }
        }

        file_put_contents($path, json_encode($cards));

        return true;
    }


    public function cardStatus($cardNumber, $Amount, $cvv, $checkCvv = false): bool
    {
        $path = storage_path('app/private/cards.json');

        if (!file_exists($path)) {
            return false;
        }

        $json = file_get_contents($path);
        $cards = json_decode($json, true);

        if (is_null($cards)) {
            return false;
        }
           
        foreach ($cards as $card) {
        $plainCardNumber = Crypt::decryptString($card['card_number']);
            if (
                $cardNumber ==  $plainCardNumber &&
                (strval($cvv) === strval($card['cvv']) || $checkCvv) &&
                Carbon::now()->lessThanOrEqualTo(Carbon::parse($card['expiry'])) &&
                $card['balance'] >= $Amount
            ) {
                return true;
            }
        }
        return false;
    }

    //helper  
    public function TotalPriceReservation($apartment_id, $start, $end, $apartmentuser)
    {
        $apartment = Apartment::where('id', $apartment_id)->first();
        if (!$apartment) {
            return null;
        }
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        if ($end->lt($start)) {
            return null;
        }
        $totalNights = $end->diffInDays($start, true) + 1;
        $totalPrice = $totalNights * $apartmentuser->priceAtBooking;
       
        return $totalPrice;
    }
}
