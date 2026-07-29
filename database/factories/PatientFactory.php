<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $cpf = fake()->numerify('###########');
        $street = fake()->streetName();
        $neighborhood = fake()->word();
        $city = fake()->city();
        return [
            'user_id'             => User::factory(),
            'date_of_birth'       => fake()->date('Y-m-d', '-18 years'),
            // CPF com paridade PII
            'cpf_encrypted'       => Crypt::encryptString($cpf),
            'cpf_hash'            => hash('sha256', $cpf),
            'main_complaint'      => fake()->sentence(3),
            // Endereço criptografado
            'street_hash'         => hash('sha256', mb_strtolower($street)),
            'street_encrypted'    => Crypt::encryptString(mb_strtolower($street)),
            'neighborhood_hash'   => hash('sha256', mb_strtolower($neighborhood)),
            'neighborhood_encrypted' => Crypt::encryptString(mb_strtolower($neighborhood)),
            'city_hash'           => hash('sha256', mb_strtolower($city)),
            'city_encrypted'      => Crypt::encryptString(mb_strtolower($city)),
            'state'               => fake()->stateAbbr(),
            'zip_code'            => fake()->numerify('#####-###'),
            'clinical_history'    => fake()->paragraph(2),
            // Telefones em texto puro
            'phone1'              => preg_replace('/\D/', '', fake()->phoneNumber()),
            'phone2'              => preg_replace('/\D/', '', fake()->phoneNumber()),
        ];
    }
}