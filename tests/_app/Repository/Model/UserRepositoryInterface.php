<?php

namespace Tests\_app\Repository\Model;

use Tests\_app\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function getByEmail(string $email): ?User;
}
