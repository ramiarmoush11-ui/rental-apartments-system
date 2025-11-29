<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required | string | between:8,255 | doesnt_start_with:0,1,2,3,4,5,6,7,8,9',
            'email' => 'required | string | email | between:11,255 |unique:users,email',
            'phone' => 'required|digits_between:10,15',
            'password' => 'required | string | min: 8 |confirmed | regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/',
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'The password must contain at least one uppercase letter,
             one lowercase letter,
             one number, 
             and one special character (@ $ ! % * ? &).',
        ];
    }
}
