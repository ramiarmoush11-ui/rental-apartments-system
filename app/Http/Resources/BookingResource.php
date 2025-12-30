<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'enType'      => $this->enType ?? 'Unknown',
            'enStatus'    => $this->enStatus ?? 'Unknown',
            'rate'      => $this->rate ?? 'Not rated yet',
            'startTerm' => $this->startTerm ? $this->startTerm->format('Y-m-d') : 'No start date',
            'endTerm'   => $this->endTerm ? $this->endTerm->format('Y-m-d') : 'No end date',
            'apartment' => new ApartmentResource($this->whenLoaded('apartment')),
            'user'      => new UserResource($this->whenLoaded('user')),
            'CreatedAt' => $this->created_at ? $this->created_at->format('Y-m-d') : 'Unknown date',
        ];
    }
}
