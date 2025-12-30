<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'name'            => $this->name ?? 'Name not available',
            'email'           => $this->email ?? 'Email not available',
            'phone'           => $this->phone ?? 'Phone not available',
            'verified'        => $this->verified ? 'Yes' : 'No',
            'role'            => $this->enRole ?? 'Role not specified',
            'isBanned'        => $this->isbanned ? 'Yes' : 'No',
            'banCount'        => $this->ban_count ?? 0,
            'banType'         => $this->ban_type ?? 'No active ban',
            'bannedUntil'     => $this->banned_until?->format('Y-m-d') ?? 'Not banned',
            'profile'         => $this->profile ? new ProfileResource($this->profile) : 'Profile not available',
            'bookings'        => $this->bookings ? BookingResource::collection($this->bookings) : [],
            'payments'        => $this->payments ? PaymentResource::collection($this->payments) : [],
            //'Apartments'    => $this->apartments ? ApartmentResource::collection($this->apartments) : [],
            'emailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i') ?? 'Not verified yet',
            'createdAt'       => $this->created_at?->format('Y-m-d') ?? 'Unknown date',
        ];

    }
}
