<?php

declare(strict_types=1);

namespace Tests\_app\Repository;

use Doctrine\ORM\EntityRepository;
use Tests\_app\Entity\User;

final class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function getByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findOneBy(['email' => $email]);

        return $user;
    }
}
