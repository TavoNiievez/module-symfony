<?php

declare(strict_types=1);

namespace Codeception\Exception;

use InvalidArgumentException;

class InvalidSessionAttributeException extends InvalidArgumentException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Attribute name must be string, %s given.', $type));
    }
}
