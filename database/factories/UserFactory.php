<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class UserFactory extends Factory
{

    protected static ?string $password;


    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'verified' => $this->faker->boolean(),
            'enRole' =>  'User',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'isbanned' => false,
            'ban_count' => $this->faker->numberBetween(0, 3),
            'remember_token' => Str::random(10),
        ];
    }


    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
