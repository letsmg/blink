<?php

namespace App\Repositories;

use App\Models\Patient;

class PatientRepository extends BaseRepository
{
    public function __construct(Patient $patient)
    {
        parent::__construct($patient);
    }

    public function findByCpfHash(string $cpfHash): ?Patient
    {
        return $this->model->where('cpf_hash', $cpfHash)->first();
    }

    public function findByUserId(int $userId): ?Patient
    {
        return $this->model->where('user_id', $userId)->first();
    }
}
