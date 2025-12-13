<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ValidatesCityAndState;

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
                function ($attribute, $value, $fail) {
                    $this->validateState($attribute, $value, $fail);
                },
            ],

            'enCity' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $this->validateCity($attribute, $value, $fail);
                },
            ],

            'price' => 'required|numeric|min:1',
            'area'  => 'required|numeric|min:1',
            'floor' => 'required|integer|min:0',

            'cardNumber' => [
                'required',
                'digits_between:13,19',
            ],
        ];
    }
}
