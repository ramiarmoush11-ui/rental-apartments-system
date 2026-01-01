<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return [
            'enCity'              => __('city_state.' . $this->enCity) ?? __('resources.city_not_specified'),
            'enState'             => __('city_state.' . $this->enState) ?? __('resources.state_not_specified'),
            'price'               => $this->price ? number_format($this->price, 2) . ' $' : __('resources.price_not_available'),
            'area'                => $this->area ?? __('resources.area_not_specified'),
            'floor'               => $this->floor ?? __('resources.floor_not_specified'),
            'rate'                => $this->rate ?? __('resources.not_rated_yet'),
            'title'               => $this->title ?? __('resources.title_not_specified'),
            'id'                  => $this->id ?? __('resources.id_not_specified'),
            'description'         => $this->description ?? __('resources.description_not_available'),
            'address_description' => $this->address_description ?? __('resources.address_description_not_available'),
            'images'              => $this->images ? $this->images : __('resources.images_not_available'),
            'CreatedAt'           => $this->created_at?->format('Y-m-d') ?? __('resources.unknown_date'),
        ];
    }
}
