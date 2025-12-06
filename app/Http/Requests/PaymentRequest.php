<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'cardNumber' => [
                'required',             // 1. يجب إدخال القيمة
                'string',               // 2. يجب أن تكون القيمة نصاً
                'digits_between:13,19', // 3. يجب أن يكون طولها بين 13 و 19 رقم (شامل)
                'numeric'
            ],
            'cvv' => [
                'required',
                 'digits_between:3,4', // فيزا وماستر 3، بعض البطاقات 4
                'numeric',
            ],
        ];
    }
}
