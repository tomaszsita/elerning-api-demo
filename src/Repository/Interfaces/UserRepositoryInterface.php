<?php

namespace App\Repository\Interfaces;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}
