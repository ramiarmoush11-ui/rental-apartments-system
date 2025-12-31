<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'name'            => $this->name ?? __('user.name_not_available'),
            'email'           => $this->email ?? __('user.email_not_available'),
            'phone'           => $this->phone ?? __('user.phone_not_available'),
            'verified'        => $this->verified ? __('user.verified_yes') : __('user.verified_no'),
            'role'            => $this->enRole ?? __('user.role_not_specified'),
            'isBanned'        => $this->isbanned ? __('user.is_banned_yes') : __('user.is_banned_no'),
            'banCount'        => $this->ban_count ?? 0,
            'banType'         => $this->ban_type ?? __('user.no_active_ban'),
            'bannedUntil'     => $this->banned_until?->format('Y-m-d') ?? __('user.not_banned'),
            'profile'         => $this->profile ? new ProfileResource($this->profile) : __('user.profile_not_available'),
            'bookings'        => $this->bookings ? BookingResource::collection($this->bookings) : [],
            'payments'        => $this->payments ? PaymentResource::collection($this->payments) : [],
            'emailVerifiedAt' => $this->email_verified_at?->format('Y-m-d H:i') ?? __('user.not_verified_yet'),
            'createdAt'       => $this->created_at?->format('Y-m-d') ?? __('user.unknown_date'),
        ];
    }
}
