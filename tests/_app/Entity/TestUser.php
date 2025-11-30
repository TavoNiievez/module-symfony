<?php

declare(strict_types=1);

namespace Tests\_app\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $userIdentifier,
        private string $password,
        private array $roles = []
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void {}
}
