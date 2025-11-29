<?php

namespace Tests\_app\Repository;

use Tests\_app\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function getByEmail(string $email): ?User;
}
