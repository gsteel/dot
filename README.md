# Dot
[![Continuous Integration](https://github.com/gsteel/dot/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/gsteel/dot/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/gsteel/dot/branch/master/graph/badge.svg?token=TjLKu5FkjA)](https://codecov.io/gh/gsteel/dot)
[![psalm coverage](https://shepherd.dev/github/gsteel/dot/coverage.svg)](https://shepherd.dev/github/gsteel/dot)
[![psalm level](https://shepherd.dev/github/gsteel/dot/level.svg)](https://shepherd.dev/github/gsteel/dot)



Retrieve strongly typed values from deeply nested arrays.

This library will not set any values or manipulate data structures in any way. It is purely a way to retrieve information.

## Usage

### Give me a typed value or throw an exception:
```php

use GSteel\Dot;
use GSteel\MissingKey;
use GSteel\InvalidValue;
use stdClass;

$data = [
    'data' => [
        'float' => 1.23,
        'integer' => 42,
        'bool' => true,
        'string' => 'Goats',
        'callable' => static fn (): string => 'Hey!',
        'instance' => new stdClass(),
        'mixed' => null,
    ],
];

$value = Dot::float('data.float', $data); // 1.23
$value = Dot::integer('data.integer', $data); // 42
$value = Dot::bool('data.bool', $data); // true
$value = Dot::string('data.string', $data); // Goats
$value = Dot::callable('data.callable', $data); // function
$value = Dot::instanceOf('data.instance', $data, stdClass::class); // object<stdClass>
$value = Dot::valueAt('data.mixed'); // mixed

$value = Dot::string('nope.not-there', $data); // Exception: MissingKey
$value = Dot::string('data.float', $data); // Exception: InvalidValue

```

### Retrieve a typed value or null

All the methods have consistent names, so `floatOrNull`, `boolOrNull` etc.

```php

use GSteel\Dot;

$data = ['a' => ['b' => ['c' => 'foo']]];

$value = Dot::stringOrNull('a.b.c', $data); // "foo"
$value = Dot::stringOrNull('a.b.nope', $data); // null
$value = Dot::integerOrNull('a.b.c', $data); // null

```

### Retrieve a typed existing value or fallback to a given default

```php

use GSteel\Dot;

$data = ['a' => ['b' => ['c' => 'foo']]];

$value = Dot::stringDefault('a.b.c', $data, 'bar'); // "foo"
$value = Dot::stringDefault('a.b.nope', $data, 'bar'); // "bar"
$value = Dot::integerDefault('a.b.c', $data, 42); // 42

```

### Dots in the array keys?

```php
use GSteel\Dot;

$data = [
    'data' => [
        'dot.here' => 'value',
        'slash/dot.' => 'value',
        'array/' => [
            'd.o.t.s' => [
                'p|pes' => 'value',
            ],      
        ],
    ],
];

$value = Dot::string('data/dot.here', $data, '/'); // "value"
$value = Dot::string('data|slash/dot.', $data, '|'); // "value"
$value = Dot::string('data*array/*d.o.t.s*p|pes', $data, '*'); // "value"
```

## Why?

As a mostly happy [psalm](https://psalm.dev) user, it is **really boring** telling Psalm the configuration array you just retrieved from your DI container has a possibly null string value somewhere. For example:

```php
use GSteel\Dot;

$config = $container->get('config');

$connectionParams = $config['doctrine']['connection']['params'] ?? [];
// 👆 Psalm has no idea what that is.

// Alternatively…

$params = [
    'host' => Dot::stringDefault('doctrine.connection.params.host', $config, 'localhost'),
    'port' => Dot::integerDefault('doctrine.connection.params.port', $config, 1234),
];
```

## Hasn't this been done before?

Yes. Here's a few:

 - [dflydev/dflydev-dot-access-data](https://github.com/dflydev/dflydev-dot-access-data)
 - [adbario/php-dot-notation](https://github.com/adbario/php-dot-notation)
 - https://packagist.org/?query=array%20dot
