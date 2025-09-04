<?php

use Symfony\Component\Validator\Constraints as Assert;

class ValidEntity
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\Length(min: 3)]
    public string $short;

    public function __construct(string $name = '', string $short = 'ab')
    {
        $this->name = $name;
        $this->short = $short;
    }
}
