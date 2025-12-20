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

        return

            [
                'enState' => [
                    'sometimes',
                    'string',
                    'required_with:enCity',
                    function ($attribute, $value, $fail) {
                        if (!$this->has('enCity') || empty($this->input('enCity'))) {
                            $fail('You cannot provide a state without specifying a city.');
                        } else {
                            $this->validateState($attribute, $value, $fail);
                        }
                    },
                ],
                'enCity' => [
                    'sometimes',
                    'string',
                    'required_with:enState',
                    function ($attribute, $value, $fail) {
                        if (!$this->has('enState') || empty($this->input('enState'))) {
                            $fail('You cannot provide a city without specifying a state.');
                        } else {
                            $this->validateCity($attribute, $value, $fail);
                        }
                    },
                ],

                'price' => [
                    'sometimes',
                    'numeric',
                    'min:1',
                    'max:1000000',
                ],
                'area' => [
                    'sometimes',
                    'numeric',
                    'min:10',
                    'max:10000',
                ],
                'floor' => [
                    'sometimes',
                    'integer',
                    'min:0',
                    'max:100',
                ],
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['string'],
                'address_description' => ['string'],
                'images' => ['sometimes', 'array'],
                'images.*' => ['image', 'mimes:png,jpg,jpeg'],

            ];
    }

    /*  public function messages()
    {
        return [
            'enState.required_with' => 'If you provide a city, you must also provide a state.',
            'enCity.required_with'  => 'If you provide a state, you must also provide a city.',
        ];
    }*/
}
