<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enType' => $this->faker->randomElement(['Owner', 'Renter']),
            'enStatus' => $this->faker->randomElement(['Pending', 'Cancelled', 'Accepted', 'AwaitingPayment']),
            'rate' => $this->faker->randomFloat(1, 1, 5),
            'startTerm' => $this->faker->date(),
            'endTerm' => $this->faker->date(),
            'priceAtBooking' => $this->faker->randomFloat(2, 100, 1000),

        ];
    }
}
