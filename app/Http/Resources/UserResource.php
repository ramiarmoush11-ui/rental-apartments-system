<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'name'            => $this->name ?? __('resources.name_not_available'),
            'email'           => $this->email ?? __('resources.email_not_available'),
            'phone'           => $this->phone ?? __('resources.phone_not_available'),
            'verified'        => $this->verified ? __('resources.verified_yes') : __('resources.verified_no'),
            'role'            => $this->enRole ?? __('resources.role_not_specified'),
            'isBanned'        => $this->isbanned ? __('resources.is_banned_yes') : __('resources.is_banned_no'),
            'banCount'        => $this->ban_count ?? 0,
            'banType'         => $this->ban_type ?? __('resources.no_active_ban'),
            'bannedUntil'     => $this->banned_until?->format('Y-m-d') ?? __('resources.not_banned'),
            'profile'         => $this->profile ? new ProfileResource($this->profile) : __('resources.profile_not_available'),
            'bookings'        => $this->bookings ? BookingResource::collection($this->bookings) : [],
            'payments'        => $this->payments ? PaymentResource::collection($this->payments) : [],
            'emailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i') ?? __('resources.not_verified_yet'),
            'createdAt'       => $this->created_at?->format('Y-m-d') ?? __('resources.unknown_date'),
        ];
    }
}
