<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'enType'      => $this->enType ?? __('booking.unknown_type'),
            'enStatus'    => $this->enStatus ?? __('booking.unknown_status'),
            'rate'        => $this->rate ?? __('booking.not_rated_yet'),
            'startTerm'   => $this->startTerm ? $this->startTerm->format('Y-m-d') : __('booking.no_start_date'),
            'endTerm'     => $this->endTerm ? $this->endTerm->format('Y-m-d') : __('booking.no_end_date'),
            'apartment'   => new ApartmentResource($this->whenLoaded('apartment')),
            'user'        => new UserResource($this->whenLoaded('user')),
            'CreatedAt'   => $this->created_at ? $this->created_at->format('Y-m-d') : __('booking.unknown_date'),
        ];
    }
}
