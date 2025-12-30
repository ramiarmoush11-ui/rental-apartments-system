<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
       return [
            'amount'     => $this->amount ? number_format($this->amount, 2) . ' $' : 'Amount not available',
            'CreatedAt'  => $this->created_at ? $this->created_at->format('Y-m-d') : 'Date not available',
            //  ما منعرض cardNumber لأنه حساس
        ];

    }
}
