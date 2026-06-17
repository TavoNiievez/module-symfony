<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidSessionAttributeException extends InvalidArgumentException
{
    public function __construct(string $type, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Attribute name must be string, %s given.', $type),
            $code,
            $previous
        );
    }
}
