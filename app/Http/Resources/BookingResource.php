<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'Type'      => $this->enType ?? 'Unknown',
            'Status'    => $this->enStatus ?? 'Unknown',
            'Rate'      => $this->rate ?? 'Not rated yet',
            'StartTerm' => $this->startTerm ? $this->startTerm->format('Y-m-d') : 'No start date',
            'EndTerm'   => $this->endTerm ? $this->endTerm->format('Y-m-d') : 'No end date',
            'Apartment' => new ApartmentResource($this->whenLoaded('apartment')),
            'User'      => new UserResource($this->whenLoaded('user')),
            'CreatedAt' => $this->created_at ? $this->created_at->format('Y-m-d') : 'Unknown date',
        ];
    }
}
