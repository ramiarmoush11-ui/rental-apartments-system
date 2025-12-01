<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class addApartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
    
        $states = config('city&state.states', []); 

        $stateKeys = array_keys($states);

        return [
            'enState' => [
                'required',
                'string',
                Rule::in($stateKeys),
            ],

            'enCity' => [
                'required',
                'string',
     
                function ($attribute, $value, $fail) use ($states) {
          
                    $state = $this->input('enState');

                
                    if (!$state || !array_key_exists($state, $states)) {
                        $fail('The selected state is invalid or missing.');
                        return;
                    }

                    $cities = $states[$state] ?? [];
                    if (!in_array($value, $cities)) {
                        $fail('The selected city does not belong to the selected state.');
                    }
                }
            ],

            'price' => 'required|numeric',
            'area'  => 'required|numeric',
            'floor' => 'required|numeric',
        ];
    }

}