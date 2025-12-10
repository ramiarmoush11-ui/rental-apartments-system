<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'Type'      => $this->enType,
            'Status'    => $this->enStatus,
            'Rate'      => $this->rate,
            'StartTerm' => $this->startTerm->format('Y-m-d'),
            'EndTerm'   => $this->endTerm->format('Y-m-d'),
            'Apartment' => new ApartmentResource($this->whenLoaded('apartment')),
            'User'      => new UserResource($this->whenLoaded('user')),
            'CreatedAt' => $this->created_at->format('Y-m-d'),
        ];
    }
}
