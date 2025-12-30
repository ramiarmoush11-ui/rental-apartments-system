<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->firstName ?? 'First name not provided',
            'lastName'  => $this->lastName ?? 'Last name not provided',
            'avatar'    => $this->avatar ?? 'No avatar available',
            'birthDate' => $this->birthDate ? $this->birthDate->format('Y-m-d') : 'Birth date not specified',
            'idPhoto'   => $this->idPhoto ?? null,
        ];
    }
}
