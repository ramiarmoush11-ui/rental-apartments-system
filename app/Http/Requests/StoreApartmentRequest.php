<?php

namespace App\Http\Requests;

use App\Traits\ValidatesCityAndState;
use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
{
    use ValidatesCityAndState;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'enState' => [
                'required',
                'string',
                [$this, 'validateState'],
            ],

            'enCity' => [
                'required',
                'string',
                [$this, 'validateCity'],
            ],

            'price' => 'required|numeric|min:1',
            'area'  => 'required|numeric|min:1',
            'floor' => 'required|integer',
            'cardNumber' => 'required|numeric|min:1'//new
        ];
    }
}
