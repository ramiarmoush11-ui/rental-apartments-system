<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->firstName ?? __('resources.first_name_not_provided'),
            'lastName'  => $this->lastName ?? __('resources.last_name_not_provided'),
            'avatar'    => $this->avatar ?? __('resources.no_avatar_available'),
            'birthDate' => $this->birthDate ? $this->birthDate->format('Y-m-d') : __('resources.birth_date_not_specified'),
            'idPhoto'   => $this->idPhoto ?? __('resources.id_photo_not_available'),
        ];
    }
}
