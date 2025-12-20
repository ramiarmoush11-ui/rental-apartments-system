<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

   
    public function rules(): array
    {
        return [
            'id' => 'prohibited',
            'user_id' => 'prohibited',
            'firstName' => 'sometimes | string | between:3,255 | doesnt_start_with:0,1,2,3,4,5,6,7,8,9',
            'lastName' => 'sometimes | string | between:3,255 | doesnt_start_with:0,1,2,3,4,5,6,7,8,9',
            'avatar' => 'sometimes|image|mimes:png,jpg,jpeg',
            'birthDate' => 'sometimes|date |before:-18 years',
            'idPhoto' => 'prohibited'
        ];
    }
}
