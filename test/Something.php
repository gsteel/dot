<?php

declare(strict_types=1);

namespace GSteel\Test;

final class Something
{
    public static function get(): string
    {
        return 'got';
    }

    public function __invoke(): string
    {
        return 'invoke';
    }
}
