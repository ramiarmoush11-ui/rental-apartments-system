<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{


    public function toArray(Request $request): array
    {
      return [
            'City'       => $this->enCity ?? 'City not specified',
            'State'      => $this->enState ?? 'State not specified',
            'Price'      => $this->price ? number_format($this->price, 2) . ' $' : 'Price not available',
            'Area'       => $this->area ?? 'Area not specified',
            'Floor'      => $this->floor ?? 'Floor not specified',
            'Rate'       => $this->rate ?? 'Not rated yet',
            'CreatedAt'  => $this->created_at?->format('Y-m-d') ?? 'Unknown date',
        ];

    }
}
