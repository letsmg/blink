<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findStaffUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereIn('role', [1, 2])->get();
    }
}
