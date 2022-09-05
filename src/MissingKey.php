<?php

declare(strict_types=1);

namespace GSteel;

use InvalidArgumentException;

use function sprintf;

final class MissingKey extends InvalidArgumentException
{
    public static function for(string $missingKey, string $requestedPath): self
    {
        return new self(
            sprintf(
                'The key "%s" does not exist in the input array. Requested path was "%s"',
                $missingKey,
                $requestedPath,
            ),
        );
    }
}
