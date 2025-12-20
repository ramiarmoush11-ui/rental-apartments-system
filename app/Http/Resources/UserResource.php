<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'Name'            => $this->name ?? 'Name not available',
            'Email'           => $this->email ?? 'Email not available',
            'Phone'           => $this->phone ?? 'Phone not available',
            'Verified'        => $this->verified ? 'Yes' : 'No',
            'Role'            => $this->enRole ?? 'Role not specified',
            'IsBanned'        => $this->isbanned ? 'Yes' : 'No',
            'BanCount'        => $this->ban_count ?? 0,
            'BanType'         => $this->ban_type ?? 'No active ban',
            'BannedUntil'     => $this->banned_until?->format('Y-m-d') ?? 'Not banned',
            'Profile'         => $this->profile ? new ProfileResource($this->profile) : 'Profile not available',
            'Bookings'        => $this->bookings ? BookingResource::collection($this->bookings) : [],
            'Payments'        => $this->payments ? PaymentResource::collection($this->payments) : [],
            //'Apartments'    => $this->apartments ? ApartmentResource::collection($this->apartments) : [],
            'EmailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i') ?? 'Not verified yet',
            'CreatedAt'       => $this->created_at?->format('Y-m-d') ?? 'Unknown date',
        ];

    }
}
