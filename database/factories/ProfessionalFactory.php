<?php

namespace Database\Factories;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Professional>
 */
class ProfessionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'specialty' => fake()->randomElement(['Cardiologia', 'Dermatologia', 'Ortopedia', 'Pediatria', 'Neurologia']),
            'professional_document' => fake()->numerify('CRM/## ####'),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
