<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'booking_id' => $this->id,
            'enType'      => $this->enType ?? __('resources.unknown_type'),
            'enStatus'    => $this->enStatus ?? __('resources.unknown_status'),
            'rate'        => $this->rate ?? __('resources.not_rated_yet'),
            'startTerm'   => $this->startTerm ? $this->startTerm->format('Y-m-d') : __('resources.no_start_date'),
            'endTerm'     => $this->endTerm ? $this->endTerm->format('Y-m-d') : __('resources.no_end_date'),
            'apartment'   => new ApartmentResource($this->whenLoaded('apartment')),
            'user'        => new UserResource($this->whenLoaded('user')),
            'CreatedAt'   => $this->created_at ? $this->created_at->format('Y-m-d') : __('resources.unknown_date'),
        ];
    }
}
