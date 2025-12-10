<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Apartment>
 */
class ApartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enCity'  => $this->faker->city(),
            'enState' => $this->faker->state(),
            'price'   => $this->faker->randomFloat(2, 100, 1000),
            'area'    => $this->faker->randomFloat(2, 50, 300),
            'floor'   => $this->faker->numberBetween(1, 10),
            'rate'    => $this->faker->randomFloat(1, 1, 5),
        ];
    }
}
