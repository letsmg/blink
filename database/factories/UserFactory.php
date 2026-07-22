<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'display_name'        => $firstName, // Apenas primeiro nome seguro para listagem
            'first_name_hash'      => hash('sha256', mb_strtolower($firstName)),
            'first_name_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString(mb_strtolower($firstName)),
            'last_name_hash'       => hash('sha256', mb_strtolower($lastName)),
            'last_name_encrypted'  => \Illuminate\Support\Facades\Crypt::encryptString(mb_strtolower($lastName)),
            'email'               => fake()->unique()->safeEmail(),
            'email_verified_at'   => now(),
            'password'            => static::$password ??= \Illuminate\Support\Facades\Hash::make('password'),
            'remember_token'      => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}