<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (range(1, 50) as $number) {
            $email = sprintf('dummy.user.%03d@example.test', $number);

            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'password' => Str::random(40),
                ],
            );
        }
    }
}
