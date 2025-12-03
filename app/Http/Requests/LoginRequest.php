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
            //'email' => 'required | string | email | between:11,255 ',
            'phone' => 'required | regex:/^(?:\+9639\d{8}|09\d{8})$/',
            'password' => 'required | string | min: 8  | regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/',
        ];
    }
}
