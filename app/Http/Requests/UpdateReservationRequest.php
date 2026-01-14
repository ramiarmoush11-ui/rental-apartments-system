<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            'startTerm'=>'required|date|after_or_equal:today',
            'endTerm'=>'required|date|after_or_equal:startTerm',
            'cardNumber' => [   'nullable',     
                'string',               
                'digits_between:13,19', 
                'numeric'
            ],
            'cvv' => ['nullable',
                 'digits_between:3,4', 
                'numeric',
            ]
        ];
    }
}
