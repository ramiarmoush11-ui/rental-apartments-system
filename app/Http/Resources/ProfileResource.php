<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->firstName ?? __('user.first_name_not_provided'),
            'lastName'  => $this->lastName ?? __('user.last_name_not_provided'),
            'avatar'    => $this->avatar ?? __('user.no_avatar_available'),
            'birthDate' => $this->birthDate ? $this->birthDate->format('Y-m-d') : __('user.birth_date_not_specified'),
            'idPhoto'   => $this->idPhoto ?? __('user.id_photo_not_available'),
        ];
    }
}
