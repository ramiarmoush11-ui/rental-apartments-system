<?php

namespace App\Traits;

trait ValidatesCityAndState
{
    public function validateState($attribute, $state, $fail)
    {
        $states = config('city&state.states', []);
        $stateKeys = array_keys($states);

        if (!in_array($state, $stateKeys)) {
            $fail(__('validation.state_invalid'));
        }
    }

    public function validateCity($attribute, $city, $fail)
    {
        $states = config('city&state.states', []);
        $state  = $this->input('enState');

        if (!$state || !array_key_exists($state, $states)) {
            $fail(__('validation.state_missing_or_invalid'));
            return;
        }

        $cities = $states[$state] ?? [];
        if (!in_array($city, $cities)) {
            $fail(__('validation.city_not_in_state'));
        }
    }
}
