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
            'enState' => ['nullable', 'string', [$this, 'validateState']],
            'enCity'  => ['nullable', 'string', [$this, 'validateCity']],

            'minPrice' => 'nullable| numeric|min:0',
            'maxPrice' => 'nullable| numeric|min:0|gte:minPrice',

            'minArea' => 'nullable|numeric|min:0',
            'maxArea' => 'nullable|numeric|min:0|gte:minArea',

            'floor' => 'nullable|integer|min:0',

            'minRate' => 'nullable|numeric|min:0|max:5',
            'maxRate' => 'nullable|numeric|min:0|max:5|gte:minRate',

            'order' => 'nullable|in:asc,desc',

        ];
    }
}
