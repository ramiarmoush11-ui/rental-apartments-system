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
    // to pay money the th3 parameter should be +
    // to return money the th3 parameter should be -

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
    //كان فيني استخدم نفس يلي فوق بس مشان غير اسماء وما نفوت بالحيط وبالاشارات
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
            if ($cardNumber == $card['card_number']) {
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
            echo($cardNumber);
        foreach ($cards as $card) {
        
            if (
                $cardNumber == $card['card_number'] &&
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
        // $apartment_id = $apartment_user['apartment_id'];
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
        //تم زيادة واحد  لانه هاد التابع لا يحسب اليوم الأخير 
        $totalPrice = $totalNights * $apartmentuser->priceAtBooking;
       
        return $totalPrice;
    }




    //عزبالة هدول الميثودين الخطة يلي براسي صار بدها رفرشة دائمة من الخادم وشغلات شوي متقدمة 
    //كنت حاطط بالي اني خلي المصاري تنتقل من pending_balance to balance وقت يبلش الحجز 
    //مشان ما خليي للمؤجر سلطة عالمصاري الا وقت يبدا الحجز رسميا
    //بس بعد ما بلشت حسيت فوتت حالي بدوامة ورح نغير اغلب منطق الدفع بكل المشروع وانا ما بدي overkill code
    public function addToPendingBalance($cardNumber, $amount): bool
    {
        $done = false;
        $json = Storage::get('private/cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['pending_balance'] += $amount;
                $done = true;
                break;
            }
        }
        Storage::put('private/cards.json', json_encode($cards));
        return $done;
    }

    public function confirmPendingAmount($cardNumber, $amount): bool
    {
        $done = false;
        $json = Storage::get('private/cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            if ($cardNumber == $card['card_number']) {
                $card['pending_balance'] -= $amount;
                $card['balance'] += $amount;
                $done = true;
                break;
            }
        }
        Storage::put('private/cards.json', json_encode($cards));
        return true;
    }
}
