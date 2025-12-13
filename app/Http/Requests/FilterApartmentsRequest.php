<?php

namespace App\Http\Requests;

use App\Traits\ValidatesCityAndState;
use Illuminate\Foundation\Http\FormRequest;

class FilterApartmentsRequest extends FormRequest
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
            'nullable',
            'string',
             function ($attribute, $value, $fail) {
                    $this->validateState($attribute, $value, $fail);
                }
        ],

        'enCity' => [
            'nullable',
            'string',
            function ($attribute, $value, $fail) {
                    $this->validateCity($attribute, $value, $fail);
                }
        ],

        'minPrice' => 'nullable|numeric|min:0',
        'maxPrice' => 'nullable|numeric|min:0',

        'minArea' => 'nullable|numeric|min:0',
        'maxArea' => 'nullable|numeric|min:0',

        'floor' => 'nullable|integer|min:0',

        'minRate' => 'nullable|numeric|min:0|max:5',
        'maxRate' => 'nullable|numeric|min:0|max:5',

        'order' => 'nullable|in:asc,desc',
    ];
}

}
