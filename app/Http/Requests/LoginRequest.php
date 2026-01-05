<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'email' => 'sometimes | string | email | between:11,255|required_without:phone ',
            'phone' => 'sometimes|required_without:email|regex:/^09[0-9]{8}$/',
            'password' => 'required | string | min: 8',
        ];
    }
}
