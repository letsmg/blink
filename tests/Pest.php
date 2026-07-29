<?php

/*
|--------------------------------------------------------------------------
| Pest Configuration for Laravel
|--------------------------------------------------------------------------
|
| Arquivo de configuração do Pest para o projeto Blink.
| Integração com Laravel via plugin pest-plugin-laravel.
|
*/

uses(Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Global Hooks & Expectations
|--------------------------------------------------------------------------
*/

// Função helper para criar usuário autenticado rapidamente nos testes
// Roles: 1=Admin, 2=AdminOperational, 3=Professional, 4=Patient
function actingAsStaff(int $role = 1): \App\Models\User
{
    $user = \App\Models\User::factory()->create([
        'role' => \App\Enums\UserRole::from($role),
    ]);

    test()->actingAs($user);

    return $user;
}

function actingAsPatient(): \App\Models\User
{
    $user = \App\Models\User::factory()->create([
        'role' => \App\Enums\UserRole::Patient,
    ]);

    test()->actingAs($user);

    return $user;
}
