<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony\Exception;

use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

class InvalidSessionAttributeException extends InvalidArgumentException
{
    public static function fromInvalidType(mixed $value): self
    {
        return new self(sprintf('Attribute name must be string, %s given.', get_debug_type($value)));
    }
}
