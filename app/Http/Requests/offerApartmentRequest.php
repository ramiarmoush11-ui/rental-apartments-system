<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class offerApartmentRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'type'=>'prohibited',
            'rate'=>'prohibited',
            'startTerm'=>'required|date|after_or_equal:today',
            'endTerm'=>'required|date|after_or_equal:startTerm',
            'cardNumber' => [
                'required',             
                'string',               
                'digits_between:13,19', 
                'numeric'
            ],
            'cvv' => [
                'required',
                 'digits_between:3,4', 
                'numeric',
            ]
        ];
    }
}
