<?php

namespace App\Http\Requests;

use App\Traits\ValidatesCityAndState;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApartmentRequest extends FormRequest
{
    use ValidatesCityAndState;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {

        return [
           /* 'enState' => [
                'sometimes',
                'string',
                [$this, 'validateState'],
            ],*/
            'enState' =>'prohibited',

            'enCity' => [
                'sometimes',
                'string',
                [$this, 'validateCity'],
            ],

            'price' => 'sometimes|numeric',
            'area'  => 'sometimes|numeric',
            'floor' => 'sometimes|numeric',
        ];
    }
}
