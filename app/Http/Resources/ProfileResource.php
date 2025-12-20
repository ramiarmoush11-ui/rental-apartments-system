<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'FirstName' => $this->firstName ?? 'First name not provided',
            'LastName'  => $this->lastName ?? 'Last name not provided',
            'Avatar'    => $this->avatar ?? 'No avatar available',
            'BirthDate' => $this->birthDate ? $this->birthDate->format('Y-m-d') : 'Birth date not specified',
            // ما منعرض idPhoto لأنه حساس ولا شو ؟
        ];
    }
}
