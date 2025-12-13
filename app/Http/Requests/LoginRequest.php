<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    // | regex:/^(?:\+9639\d{8}|09\d{8})$/
    //  | regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/
    public function rules(): array
    {
        return [
            'email' => 'sometimes | string | email | between:11,255|required_without:phone ',
            'phone' => 'sometimes|required_without:email',
            'password' => 'required | string | min: 8',
        ];
    }
}
