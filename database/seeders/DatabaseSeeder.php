<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;


    public function run(): void
    {

        $this->call(AdminSeeder::class);

        $users = User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) {
                $user->profile()->create(
                    Profile::factory()->make()->toArray()
                );
            });

        $apartments = Apartment::factory()
            ->count(5)
            ->create();

        $bookings = Booking::factory()
            ->count(20)
            ->make()
            ->each(function ($booking) use ($users, $apartments) {
                $booking->user_id = $users->random()->id;
                $booking->apartment_id = $apartments->random()->id;
                $booking->save();
            });

        $bookings->each(function ($booking) {
            $booking->payments()->create(
                Payment::factory()->make()->toArray()
            );
        });
    }
}
