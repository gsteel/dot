<?php

declare(strict_types=1);

namespace GSteel\Test;

use GSteel\Dot;
use GSteel\EmptyPathError;
use GSteel\InvalidValue;
use GSteel\MissingKey;
use PHPUnit\Framework\TestCase;

use function assert;
use function sprintf;

class DotTest extends TestCase
{
    /** @var array<array-key, mixed> */
    private array $input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = [
            'a' => [
                'b' => [
                    'int' => 1,
                    'bool' => true,
                    'float' => 2.5,
                    'string' => 'string',
                    'null' => null,
                    'instance' => new Something(),
                    'callable' => static fn (): string => 'Hey!',
                    'callable-array' => [Something::class, 'get'],
                    'c' => [
                        'd' => [
                            'value' => 'value',
                        ],
                    ],
                ],
            ],
            0 => [
                'a' => [
                    1 => 'foo',
                ],
                1 => 'one',
            ],
            'delim' => [
                'contains.dot' => true,
                'contains/slash' => true,
                'contains|pipe' => true,
            ],
            'top.dot' => true,
            'top/slash' => true,
            'top|pipe' => true,
        ];
    }

    /** @return array<array-key, array{0: string, 1: mixed, 2: string}> */
    public function foundValueProvider(): array
    {
        return [
            ['a.b.int', 1, '.'],
            ['a.b.bool', true, '.'],
            ['a.b.float', 2.5, '.'],
            ['a.b.string', 'string', '.'],
            ['a.b.null', null, '.'],
            ['a.b.c.d.value', 'value', '.'],
            ['0.a.1', 'foo', '.'],
            ['0.1', 'one', '.'],
            ['delim/contains.dot', true, '/'],
            ['delim.contains/slash', true, '.'],
            ['delim.contains|pipe', true, '.'],
            ['top.dot', true, '/'],
            ['top/slash', true, '*'],
            ['top|pipe', true, '!'],
        ];
    }

    /** @return array<array-key, array{0: string, 1: string}> */
    public function missingPathProvider(): array
    {
        return [
            // request path, fails at
            ['top-level-not-set', 'top-level-not-set'],
            ['a.unset-level-one', 'a.unset-level-one'],
            ['a.nested-unset.other.keys', 'a.nested-unset'],
            ['a.b.int.nope', 'a.b.int.nope'],
            ['0.a.3', '0.a.3'],
            ['0.a.1.2', '0.a.1.2'],
        ];
    }

    public function testAnEmptyPathIsExceptionalInValueAt(): void
    {
        $this->expectException(EmptyPathError::class);
        $this->expectExceptionMessage('An empty array path was provided');

        Dot::valueAt('', []);
    }

    public function testAPathEqualToTheDelimiterIsExceptionalInValueAt(): void
    {
        $this->expectException(EmptyPathError::class);
        $this->expectExceptionMessage('An empty array path was provided');

        Dot::valueAt('!', [], '!');
    }

    /** @dataProvider foundValueProvider */
    public function testValueAtCanReturnTheExpectedValue(string $path, mixed $expect, string $delimiter): void
    {
        self::assertEquals($expect, Dot::valueAt($path, $this->input, $delimiter));
    }

    public function testAnEmptyPathIsExceptionalInValueOrNull(): void
    {
        $this->expectException(EmptyPathError::class);
        $this->expectExceptionMessage('An empty array path was provided');

        Dot::valueOrNull('', []);
    }

    public function testAPathEqualToTheDelimiterIsExceptionalInValueOrNull(): void
    {
        $this->expectException(EmptyPathError::class);
        $this->expectExceptionMessage('An empty array path was provided');

        Dot::valueOrNull('!', [], '!');
    }

    /** @dataProvider foundValueProvider */
    public function testThatValueOrNullWillReturnTheExpectedValue(string $path, mixed $expect, string $delimiter): void
    {
        self::assertEquals($expect, Dot::valueOrNull($path, $this->input, $delimiter));
    }

    /** @dataProvider missingPathProvider */
    public function testMissingKeyIsThrownWhenTheKeyIsNotSet(string $requestedPath, string $failsAt): void
    {
        $this->expectException(MissingKey::class);
        $this->expectExceptionMessage(sprintf(
            'The key "%s" does not exist in the input array. Requested path was "%s"',
            $failsAt,
            $requestedPath
        ));

        Dot::valueAt($requestedPath, $this->input);
    }

    /** @dataProvider missingPathProvider */
    public function testMissingKeyIsNotThrownFromValueOrNull(string $requestedPath): void
    {
        self::assertNull(Dot::valueOrNull($requestedPath, $this->input));
    }

    public function testThatUsingADelimiterThatIsPartOfAKeyWillCauseAnException(): void
    {
        $this->expectException(MissingKey::class);
        $this->expectExceptionMessage('The key "delim.contains" does not exist in the input array. Requested path was "delim.contains.dot"');

        Dot::valueAt('delim.contains.dot', $this->input, '.');
    }

    public function testThatCustomDelimiterCanUseMultipleCharacters(): void
    {
        self::assertSame(1, Dot::valueAt('a...b...int', $this->input, '...'));
    }

    public function testInteger(): void
    {
        self::assertSame(1, Dot::integer('a.b.int', $this->input));
    }

    public function testIncorrectIntegerType(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.bool" was expected to be "int", but "boolean" was found');
        Dot::integer('a.b.bool', $this->input);
    }

    public function testIntegerOrNull(): void
    {
        self::assertSame(1, Dot::integerOrNull('a.b.int', $this->input));
        self::assertSame(1, Dot::integerOrNull('a/b/int', $this->input, '/'));
        self::assertNull(Dot::integerOrNull('a.b.float', $this->input));
        self::assertNull(Dot::integerOrNull('a.b.not-there', $this->input));
    }

    public function testIntegerDefault(): void
    {
        self::assertSame(1, Dot::integerDefault('a.b.int', $this->input, 2));
        self::assertSame(2, Dot::integerDefault('a.b.nope', $this->input, 2));
    }

    public function testString(): void
    {
        self::assertSame('string', Dot::string('a.b.string', $this->input));
    }

    public function testIncorrectStringType(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.bool" was expected to be "string", but "boolean" was found');
        Dot::string('a.b.bool', $this->input);
    }

    public function testStringOrNull(): void
    {
        self::assertSame('string', Dot::stringOrNull('a.b.string', $this->input));
        self::assertSame('string', Dot::stringOrNull('a/b/string', $this->input, '/'));
        self::assertNull(Dot::stringOrNull('a.b.float', $this->input));
        self::assertNull(Dot::stringOrNull('a.b.not-there', $this->input));
    }

    public function testStringDefault(): void
    {
        self::assertSame('string', Dot::stringDefault('a.b.string', $this->input, 'default'));
        self::assertSame('default', Dot::stringDefault('a.b.nope', $this->input, 'default'));
    }

    public function testFloat(): void
    {
        self::assertSame(2.5, Dot::float('a.b.float', $this->input));
    }

    public function testIncorrectFloatType(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.bool" was expected to be "float", but "boolean" was found');
        Dot::float('a.b.bool', $this->input);
    }

    public function testFloatOrNull(): void
    {
        self::assertSame(2.5, Dot::floatOrNull('a.b.float', $this->input));
        self::assertSame(2.5, Dot::floatOrNull('a/b/float', $this->input, '/'));
        self::assertNull(Dot::floatOrNull('a.b.int', $this->input));
        self::assertNull(Dot::floatOrNull('a.b.not-there', $this->input));
    }

    public function testFloatDefault(): void
    {
        self::assertSame(2.5, Dot::floatDefault('a.b.float', $this->input, 5.5));
        self::assertSame(5.5, Dot::floatDefault('a.b.nope', $this->input, 5.5));
    }

    public function testBool(): void
    {
        self::assertSame(true, Dot::bool('a.b.bool', $this->input));
    }

    public function testIncorrectBoolType(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.int" was expected to be "bool", but "integer" was found');
        Dot::bool('a.b.int', $this->input);
    }

    public function testBoolOrNull(): void
    {
        self::assertSame(true, Dot::boolOrNull('a.b.bool', $this->input));
        self::assertSame(true, Dot::boolOrNull('a/b/bool', $this->input, '/'));
        self::assertNull(Dot::boolOrNull('a.b.int', $this->input));
        self::assertNull(Dot::boolOrNull('a.b.not-there', $this->input));
    }

    public function testBoolDefault(): void
    {
        self::assertSame(true, Dot::boolDefault('a.b.bool', $this->input, false));
        self::assertSame(false, Dot::boolDefault('a.b.nope', $this->input, false));
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(Something::class, Dot::instanceOf('a.b.instance', $this->input, Something::class));
    }

    public function testIncorrectInstanceOf(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.int" was expected to be "' . Something::class . '", but "integer" was found');
        Dot::instanceOf('a.b.int', $this->input, Something::class);
    }

    public function testInstanceOfOrNull(): void
    {
        self::assertInstanceOf(Something::class, Dot::instanceOfOrNull('a.b.instance', $this->input, Something::class));
        self::assertInstanceOf(Something::class, Dot::instanceOfOrNull('a/b/instance', $this->input, Something::class, '/'));
        self::assertNull(Dot::instanceOfOrNull('a.b.int', $this->input, Something::class));
        self::assertNull(Dot::instanceOfOrNull('a.b.not-there', $this->input, Something::class));
    }

    public function testInstanceOfDefault(): void
    {
        $default = new Something();
        $existing = Dot::valueOrNull('a.b.instance', $this->input);
        assert($existing instanceof Something);
        self::assertNotSame($default, $existing);

        self::assertSame($existing, Dot::instanceOfDefault('a.b.instance', $this->input, Something::class, $default));
        self::assertSame($default, Dot::instanceOfDefault('a.b.nope', $this->input, Something::class, $default));
    }

    public function testCallable(): void
    {
        $expect = Dot::valueOrNull('a.b.callable', $this->input);
        self::assertIsCallable($expect);

        self::assertSame($expect, Dot::callable('a.b.callable', $this->input));
    }

    public function testCallableObjects(): void
    {
        $expect = Dot::valueOrNull('a.b.instance', $this->input);
        self::assertInstanceOf(Something::class, $expect);

        $callable = Dot::callable('a.b.instance', $this->input);
        self::assertSame($expect, $callable);
        self::assertEquals('invoke', $callable());
    }

    public function testCallableArray(): void
    {
        $expect = Dot::valueOrNull('a.b.callable-array', $this->input);
        self::assertIsCallable($expect);

        $callable = Dot::callable('a.b.callable-array', $this->input);
        self::assertSame($expect, $callable);
        self::assertEquals('got', $callable());
    }

    public function testIncorrectCallableType(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('The value at "a.b.bool" was expected to be "callable", but "boolean" was found');
        Dot::callable('a.b.bool', $this->input);
    }

    public function testCallableOrNull(): void
    {
        $expect = Dot::valueOrNull('a.b.callable', $this->input);
        self::assertIsCallable($expect);

        self::assertSame($expect, Dot::callableOrNull('a.b.callable', $this->input));
        self::assertSame($expect, Dot::callableOrNull('a/b/callable', $this->input, '/'));
        self::assertNull(Dot::callableOrNull('a.b.int', $this->input));
        self::assertNull(Dot::callableOrNull('a.b.not-there', $this->input));
    }

    public function testCallableDefault(): void
    {
        $expect = Dot::valueOrNull('a.b.callable', $this->input);
        self::assertIsCallable($expect);
        $default = static fn (): string => 'Foo';

        self::assertSame($expect, Dot::callableDefault('a.b.callable', $this->input, $default));
        self::assertSame($default, Dot::callableDefault('a.b.nope', $this->input, $default));
    }
}
