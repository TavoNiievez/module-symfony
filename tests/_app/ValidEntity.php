<?php

use Symfony\Component\Validator\Constraints as Assert;

class ValidEntity
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(min: 6)]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    private string $password;

    public function __construct(string $email = 'test@example.com', string $password = 'password123')
    {
        $this->email = $email;
        $this->password = $password;
    }

    public static function create(string $email, string $password): self
    {
        return new self($email, $password);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
