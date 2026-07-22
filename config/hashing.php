<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Laravel uses the bcrypt driver by default.
    | For healthcare data protection, we mandate Argon2id with tuned hardware
    | parameters as defined in the project's security policy.
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Argon2id Tuned Hardware Parameters
    |--------------------------------------------------------------------------
    |
    | Configuração obrigatória para conformidade com a política de segurança:
    | - Memory cost: 64MB (65536 KiB)
    | - Time cost: 3 iterations
    | - Threads: 2 parallel threads
    |
    | Estes parâmetros garantem resistência contra ataques de força bruta
    | com hardware especializado (ASIC/FPGA) e ataques side-channel.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),  // 64MB in KiB
        'threads' => env('ARGON_THREADS', 2),
        'time' => env('ARGON_TIME', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bcrypt (fallback) Configuration
    |--------------------------------------------------------------------------
    |
    | Mantido apenas para compatibilidade com dados legados.
    | Todos os novos registros devem usar Argon2id.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
    ],

];