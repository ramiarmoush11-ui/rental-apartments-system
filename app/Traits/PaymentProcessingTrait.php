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

        for ($i = 0; $i < count($userCardNumbers); $i++) {
            if ($this->cardStatus($userCardNumbers[$i], 0, 0, true)) {
                $Refund1 = true;
                $this->completePayment($userCardNumbers[$i], -1 * $amount);
                break;
            }
        }

        for ($i = 0; $i < count($ownerCardsNumber); $i++) {
            if ($this->cardStatus($ownerCardsNumber[$i], 0, 0, true)) {
                $Refund2 = true;
                $this->completePayment($ownerCardsNumber[$i], $amount);
                break;
            }
        }
        return $Refund1 && $Refund2;
    }
    //كان فيني استخدم نفس يلي فوق بس مشان غير اسماء وما نفوت بالحيط وبالاشارات
    public function payMoney($ownerCardsNumber, $userCardNumbers, $amount): bool
    {
        $Paid1 = false;
        $Paid2 = false;

        for ($i = 0; $i < count($userCardNumbers); $i++) {
            if ($this->cardStatus($userCardNumbers[$i], 0, 0, true)) {
                $Paid1 = true;
                $this->completePayment($userCardNumbers[$i], $amount);
                break;
            }
        }

        for ($i = 0; $i < count($ownerCardsNumber); $i++) {
            if ($this->cardStatus($ownerCardsNumber[$i], 0, 0, true)) {
                $Paid2 = true;
                $this->completePayment($ownerCardsNumber[$i], -1 * $amount);
                break;
            }
        }

        return $Paid1 && $Paid2;
    }

    //helper
    public function calculateRemainingAfterDeposit($Amount)
    {
        return $Amount * 0.9;
    }

    public function calculateDeposit($Amount)
    {
        return $Amount * 0.1;
    }

    // $request->input('cardNumber') //helper
    public function completePayment($cardNumber, $amount)
    {
        $json = Storage::get('private/cards.json');
        $cards = json_decode($json, true);

        foreach ($cards as &$card) {
            $plainCardNumber = Crypt::decryptString($card['card_number']);

            if ($cardNumber == $plainCardNumber) {
                $card['balance'] -= $amount;
                break;
            }
        }

        Storage::put('private/cards.json', json_encode($cards));
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
