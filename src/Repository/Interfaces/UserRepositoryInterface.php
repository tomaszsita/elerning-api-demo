<?php

namespace App\Repository\Interfaces;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function find(mixed $id, $lockMode = null, ?int $lockVersion = null): ?object;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}
