<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'enCity' => $this->enCity,
            'enState' => $this->enState,
            'Price' => number_format($this->price, 2) . ' $',
            'area' => $this->area,
            'floor' => $this->floor,
            'rate' => $this->rate,
            'created_at' => $this->format('Y-m-d'),
        ];
    }
}
