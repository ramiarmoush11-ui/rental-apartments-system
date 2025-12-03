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
            'startTerm'=>'date|after_or_equal:today',
            'endTerm'=>'date|after_or_equal:startTerm',
        ];
    }
}
