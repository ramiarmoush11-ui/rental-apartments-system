<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|between:8,255',
            'email'    => 'required|string|email|between:11,255|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
    //|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/
    //|not_regex:/^[0-9]/
//|regex:/^09[0-9]{8}$/
   /* public function messages()
    {
        return [
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@ $ ! % * ? &).',
        ];
    }*/
}
