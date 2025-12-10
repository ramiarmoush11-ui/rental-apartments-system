<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'phone' => '0999999999',
            'verified' => true,
            'enRole' => 'Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
            'isbanned' => false,
            'ban_count' => 0,
        ]);

        $admin->profile()->updateOrCreate([], [
            'firstName' => 'Admin',
            'lastName' => 'Master',
            'avatar' => 'admin.jpg',
            'birthDate' => '1990-01-01',
            'idPhoto' => 'id.jpg',
        ]);
    }
}
