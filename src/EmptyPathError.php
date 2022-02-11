<?php

declare(strict_types=1);

namespace GSteel;

use InvalidArgumentException;

final class EmptyPathError extends InvalidArgumentException
{
    public static function new(): self
    {
        return new self('An empty array path was provided');
    }
}
