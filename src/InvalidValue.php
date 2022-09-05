<?php

declare(strict_types=1);

namespace GSteel;

use UnexpectedValueException;

use function gettype;
use function sprintf;

final class InvalidValue extends UnexpectedValueException
{
    /** @param mixed $value */
    public static function for(string $path, string $expected, $value): self
    {
        return new self(sprintf(
            'The value at "%s" was expected to be "%s", but "%s" was found',
            $path,
            $expected,
            gettype($value),
        ));
    }
}
