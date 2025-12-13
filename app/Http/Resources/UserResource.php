<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'Name' => $this->name,
            'Email' => $this->email,
            'Phone' => $this->phone,
            'Verified' => $this->verified ? 'Yes' : 'No',
            'Role' => $this->enRole,
            'IsBanned' => $this->isbanned ? 'Yes' : 'No',
            'BanCount' => $this->ban_count,
            'BanType' => $this->ban_type,
            'BannedUntil'  => $this->banned_until?->format('Y-m-d'),
            'Profile'  => new ProfileResource($this->whenLoaded('profile')),
            'Bookings' => BookingResource::collection($this->whenLoaded('bookings')),
            'Payments' => PaymentResource::collection($this->whenLoaded('payments')),
            //'Apartments'=> ApartmentResource::collection($this->whenLoaded('apartments')),
            'EmailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i'),
            'CreatedAt' => $this->created_at->format('Y-m-d'),
        ];
    }
}
