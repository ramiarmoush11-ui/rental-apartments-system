<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'avatar'    => $this->avatar,
            'birthDate' => $this->birthDate->format('Y-m-d'),
            // ما منعرض idPhoto لأنه حساس ولا شو ؟
        ];
    }
}
