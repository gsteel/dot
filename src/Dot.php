<?php

declare(strict_types=1);

namespace GSteel;

use function array_key_exists;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_string;
use function strlen;
use function trim;

/** @psalm-immutable */
final class Dot
{
    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function integer(string $path, array $array, string $pathDelimiter = '.'): int
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_int($value)) {
            throw InvalidValue::for($path, 'int', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function integerOrNull(string $path, array $array, string $pathDelimiter = '.'): int|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_int($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function integerDefault(string $path, array $array, int $default, string $pathDelimiter = '.'): int
    {
        return self::integerOrNull($path, $array, $pathDelimiter) ?? $default;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function string(string $path, array $array, string $pathDelimiter = '.'): string
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_string($value)) {
            throw InvalidValue::for($path, 'string', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $path
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     *
     * @return non-empty-string
     */
    public static function nonEmptyString(string $path, array $array, string $pathDelimiter = '.'): string
    {
        $value = self::string($path, $array, $pathDelimiter);
        if ($value === '' || trim($value) === '') {
            throw InvalidValue::for($path, 'non-empty-string', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function stringOrNull(string $path, array $array, string $pathDelimiter = '.'): string|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function stringDefault(string $path, array $array, string $default, string $pathDelimiter = '.'): string
    {
        return self::stringOrNull($path, $array, $pathDelimiter) ?? $default;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function float(string $path, array $array, string $pathDelimiter = '.'): float
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_float($value)) {
            throw InvalidValue::for($path, 'float', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function floatOrNull(string $path, array $array, string $pathDelimiter = '.'): float|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_float($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function floatDefault(string $path, array $array, float $default, string $pathDelimiter = '.'): float
    {
        return self::floatOrNull($path, $array, $pathDelimiter) ?? $default;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function bool(string $path, array $array, string $pathDelimiter = '.'): bool
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_bool($value)) {
            throw InvalidValue::for($path, 'bool', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function boolOrNull(string $path, array $array, string $pathDelimiter = '.'): bool|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_bool($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function boolDefault(string $path, array $array, bool $default, string $pathDelimiter = '.'): bool
    {
        return self::boolOrNull($path, $array, $pathDelimiter) ?: $default;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param class-string<T>         $class
     * @param non-empty-string        $pathDelimiter
     *
     * @return T
     *
     * @template T
     */
    public static function instanceOf(string $path, array $array, string $class, string $pathDelimiter = '.'): object
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! $value instanceof $class) {
            throw InvalidValue::for($path, $class, $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param class-string<T>         $class
     * @param non-empty-string        $pathDelimiter
     *
     * @return T|null
     *
     * @template T
     */
    public static function instanceOfOrNull(string $path, array $array, string $class, string $pathDelimiter = '.'): object|null
    {
        try {
            return self::instanceOf($path, $array, $class, $pathDelimiter);
        } catch (InvalidValue) {
            return null;
        } catch (MissingKey) {
            return null;
        }
    }

    /**
     * @param array<array-key, mixed> $array
     * @param class-string<T>         $class
     * @param T                       $default
     * @param non-empty-string        $pathDelimiter
     *
     * @return T
     *
     * @template T
     */
    public static function instanceOfDefault(string $path, array $array, string $class, object $default, string $pathDelimiter = '.'): object
    {
        return self::instanceOfOrNull($path, $array, $class, $pathDelimiter) ?: $default;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     */
    public static function callable(string $path, array $array, string $pathDelimiter = '.'): callable
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_callable($value)) {
            throw InvalidValue::for($path, 'callable', $value);
        }

        return $value;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function callableOrNull(string $path, array $array, string $pathDelimiter = '.'): callable|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_callable($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     */
    public static function callableDefault(string $path, array $array, callable $default, string $pathDelimiter = '.'): callable
    {
        return self::callableOrNull($path, $array, $pathDelimiter) ?? $default;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     *
     * @return array<array-key, mixed>
     */
    public static function array(string $path, array $array, string $pathDelimiter = '.'): array
    {
        $value = self::valueAt($path, $array, $pathDelimiter);
        if (! is_array($value)) {
            throw InvalidValue::for($path, 'array', $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     *
     * @return array<array-key, mixed>|null
     */
    public static function arrayOrNull(string $path, array $array, string $pathDelimiter = '.'): array|null
    {
        $value = self::valueOrNull($path, $array, $pathDelimiter);
        if (! is_array($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param array<array-key, mixed> $default
     * @param non-empty-string        $pathDelimiter
     *
     * @return array<array-key, mixed>
     */
    public static function arrayDefault(string $path, array $array, array $default, string $pathDelimiter = '.'): array
    {
        return self::arrayOrNull($path, $array, $pathDelimiter) ?? $default;
    }

    /**
     * @param non-empty-string        $pathDelimiter
     * @param array<array-key, mixed> $array
     */
    public static function valueAt(string $path, array $array, string $pathDelimiter = '.'): mixed
    {
        $keys = self::keys($path, $pathDelimiter);
        $currentValue = $array;
        $traversed = [];

        foreach ($keys as $key) {
            $traversed[] = $key;
            if (! is_array($currentValue) || ! array_key_exists($key, $currentValue)) {
                throw MissingKey::for(implode($pathDelimiter, $traversed), $path);
            }

            /** @psalm-var mixed $currentValue */
            $currentValue = $currentValue[$key];
        }

        return $currentValue;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string        $pathDelimiter
     */
    public static function valueOrNull(string $path, array $array, string $pathDelimiter = '.'): mixed
    {
        try {
            return self::valueAt($path, $array, $pathDelimiter);
        } catch (MissingKey) {
            return null;
        }
    }

    /**
     * @param non-empty-string $pathDelimiter
     *
     * @return list<string>
     */
    private static function keys(string $path, string $pathDelimiter = '.'): array
    {
        if (strlen($path) === 0 || $path === $pathDelimiter) {
            throw EmptyPathError::new();
        }

        return explode($pathDelimiter, $path);
    }
}
