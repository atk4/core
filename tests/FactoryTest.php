<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Factory;
use Atk4\Core\HookBreaker;
use Atk4\Core\Phpunit\TestCase;

class FactoryTest extends TestCase
{
    public function testMerge1(): void
    {
        // string become array
        self::assertSame(
            ['hello'],
            Factory::mergeSeeds(['hello'], null)
        );

        // left-most value is used
        self::assertSame(
            ['one'],
            Factory::mergeSeeds(['one'], ['two'], ['three'], ['four'])
        );

        // nulls are ignored
        self::assertSame(
            ['two'],
            Factory::mergeSeeds(null, ['two'], ['three'], ['four'])
        );

        // object takes precedence
        $o = new FactoryTestDiMock();
        self::assertSame(
            $o,
            Factory::mergeSeeds(null, ['two'], $o, ['four'])
        );

        // but 2 or more objects throw
        $o1 = new FactoryTestDiMock();
        $o2 = new FactoryTestDiMock();

        $this->expectException(Exception::class);
        Factory::mergeSeeds(null, ['two'], $o2, $o1, ['four']);
    }

    public function testMerge2(): void
    {
        // array argument merging
        self::assertSame(
            ['a1', 'a2'],
            Factory::mergeSeeds(['a1', 'a2'], null, ['b1', 'b2'])
        );

        // nulls are ignored
        self::assertSame(
            ['b1', 'a2', 'c3'],
            Factory::mergeSeeds([null, 'a2', null], ['b1'], ['c1', null, 'c3'])
        );

        // object takes precedence
        $o = new FactoryTestDiMock();
        self::assertSame(
            $o,
            Factory::mergeSeeds(['a1'], $o)
        );

        // is object is wrapped in array - we dont care
        $o = new FactoryTestDiMock();
        self::assertSame(
            ['b1', 'a2', 'c3'],
            Factory::mergeSeeds([null, 'a2', null], ['b1'], ['c1', null, 'c3'], [1 => $o])
        );

        // but constructor arguments (except silently ignored class name)
        // for already instanced object are not valid
        $o = new FactoryTestDiMock();

        $this->expectException(Exception::class);
        Factory::mergeSeeds(['a1', 'a2'], $o);
    }

    public function testMerge3(): void
    {
        // key/value support
        self::assertSame(
            ['a' => 1],
            Factory::mergeSeeds(['a' => 1], ['a' => 2])
        );

        // values has no special treatment
        self::assertSame(
            ['a' => [1]],
            Factory::mergeSeeds(['a' => [1]], ['a' => 2])
        );

        // object is injected with values
        $o = new FactoryTestDiMock();
        $oo = Factory::mergeSeeds(['foo' => 1], $o);

        self::assertSame($o, $oo);
        self::assertSame(1, $oo->foo);

        // even it already has value
        $o = new FactoryTestDiMock();
        $o->foo = 5;
        $oo = Factory::mergeSeeds(['foo' => 1], $o);

        self::assertSame($o, $oo);
        self::assertSame(1, $oo->foo);

        // but this way existing value is respected
        $o = new FactoryTestDiMock();
        $o->foo = 5;
        $oo = Factory::mergeSeeds($o, ['foo' => 1]);

        self::assertSame($o, $oo);
        self::assertSame(5, $oo->foo);
    }

    public function testMerge4(): void
    {
        // array values don't overwrite but rather merge
        $o = new FactoryTestViewMock();
        $o->foo = ['red'];
        $oo = Factory::mergeSeeds(['foo' => ['green']], $o);

        self::assertSame($o, $oo);
        self::assertSame(['red', 'green'], $oo->foo);

        $oo = Factory::mergeSeeds($o, ['foo' => ['white']]);
        self::assertSame($o, $oo);
        self::assertSame(['white', 'red', 'green'], $oo->foo);

        // still we don't care if they are to the right of the object
        $o = new FactoryTestDiMock();
        $o->foo = ['red'];
        $oo = Factory::mergeSeeds($o, ['foo' => ['green']]);

        self::assertSame($o, $oo);
        self::assertSame(['red'], $oo->foo);
    }

    public function testMerge5(): void
    {
        // works even if more arguments present
        $o = new FactoryTestViewMock();
        $o->foo = ['red'];
        $oo = Factory::mergeSeeds(['foo' => ['green']], $o);
        $oo = Factory::mergeSeeds(['foo' => ['xx']], $o);

        self::assertSame($o, $oo);
        self::assertSame(['red', 'green', 'xx'], $oo->foo);

        // also without arrays
        $o = new FactoryTestDiMock();
        $o->foo = 'red';
        $oo = Factory::mergeSeeds(['foo' => 'xx'], ['foo' => 'green'], $o, ['foo' => 5]);

        self::assertSame($o, $oo);
        self::assertSame('xx', $oo->foo);
    }

    public function testMerge6(): void
    {
        $oo = Factory::mergeSeeds([4 => 'four'], [5 => 'five']);
        self::assertSame([4 => 'four', 5 => 'five'], $oo);

        $oo = Factory::mergeSeeds([4 => ['four']], [5 => ['five']]);
        self::assertSame([4 => ['four'], 5 => ['five']], $oo);

        $oo = Factory::mergeSeeds([4 => 'four'], [5 => 'five'], [6 => 'six']);
        self::assertSame([4 => 'four', 5 => 'five', 6 => 'six'], $oo);

        $oo = Factory::mergeSeeds(['x' => ['four']], ['x' => ['five']]);
        self::assertSame(['x' => ['four']], $oo);

        $oo = Factory::mergeSeeds([4 => ['four']], [4 => ['five']]);
        self::assertSame([4 => ['four']], $oo);

        $oo = Factory::mergeSeeds([4 => ['200']], [4 => ['201']]);
        self::assertSame([4 => ['200']], $oo);
    }

    public function testMergeNoDiFail(): void
    {
        $o = new FactoryTestMock();
        $o->foo = ['red'];

        $this->expectException(Exception::class);
        Factory::mergeSeeds($o, ['foo' => 5]);
    }

    public function testBasic(): void
    {
        $s1 = Factory::factory([FactoryTestMock::class]);
        self::assertSame([], $s1->args);

        $s1 = Factory::factory(new FactoryTestMock());
        self::assertSame([], $s1->args);

        $s1 = Factory::factory(new FactoryTestMock('hello', 'world'));
        self::assertSame(['hello', 'world'], $s1->args);

        $s1 = Factory::factory(new FactoryTestMock(null, 'world'));
        self::assertSame([null, 'world'], $s1->args);
    }

    public function testInjection(): void
    {
        $s1 = Factory::factory(new FactoryTestDiMock(), null); // @phpstan-ignore argument.type
        self::assertNotSame('bar', $s1->foo);

        $s1 = Factory::factory(new FactoryTestDiMock(), ['foo' => 'bar']);
        self::assertSame('bar', $s1->foo);
    }

    public function testArguments(): void
    {
        $s1 = Factory::factory([FactoryTestMock::class, 'hello']);
        self::assertSame(['hello'], $s1->args);

        $s1 = Factory::factory([FactoryTestMock::class, 'hello', 'world']);
        self::assertSame(['hello', 'world'], $s1->args);

        $s1 = Factory::factory([FactoryTestMock::class, null, 'world']);
        self::assertSame([null, 'world'], $s1->args);

        $s1 = Factory::factory([FactoryTestDiMock::class, 'hello', 'foo' => 'bar', 'world']);
        self::assertSame(['hello', 'world'], $s1->args);
        self::assertSame('bar', $s1->foo);
    }

    public function testDefaults(): void
    {
        $s1 = Factory::factory([FactoryTestDiMock::class, 'hello', 'foo' => 'bar', 'world'], ['more', 'baz' => '', 'more', 'args']);
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['hello', 'world', 'args'], $s1->args);
        self::assertSame('bar', $s1->foo);
        self::assertSame('', $s1->baz);

        $s1->setDefaults([]);
    }

    public function testNull(): void
    {
        $s1 = Factory::factory([FactoryTestDiMock::class, 'foo' => null, null, 'world'], ['more', 'foo' => 'bar', 'more', 'args']);
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['more', 'world', 'args'], $s1->args);
        self::assertSame('bar', $s1->foo);

        $s1 = Factory::factory(Factory::mergeSeeds([FactoryTestDiMock::class, 'foo' => null, null, 'world'], [FactoryTestMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['more', 'world', 'args'], $s1->args);
        self::assertSame('bar', $s1->foo);

        $s1 = Factory::factory(Factory::mergeSeeds([null, 'foo' => null, null, 'world'], [FactoryTestDiMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['more', 'world', 'args'], $s1->args);
        self::assertSame('bar', $s1->foo);

        $s1 = Factory::factory(Factory::mergeSeeds(null, [FactoryTestDiMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['more', 'more', 'args'], $s1->args);
        self::assertSame('bar', $s1->foo);

        $s1 = Factory::factory(Factory::mergeSeeds([], [FactoryTestDiMock::class, 'test']));
        self::assertSame(['test'], $s1->args);
    }

    public function testDefaultsObject(): void
    {
        $this->expectException(Exception::class);
        Factory::factory([new FactoryTestDiMock(), 'foo' => 'bar'], ['baz' => '', 'foo' => 'default']);
    }

    public function testMerge(): void
    {
        $s1 = Factory::factory([FactoryTestDiMock::class, 'hello', 'world'], ['more', 'more', 'args']);
        self::assertSame(['hello', 'world', 'args'], $s1->args);

        $s1 = Factory::factory([FactoryTestDiMock::class, null, 'world'], ['more', 'more', 'args']);
        self::assertSame(['more', 'world', 'args'], $s1->args);
    }

    public function testMerge7(): void
    {
        $s1 = Factory::mergeSeeds(new FactoryTestDefMock(), ['foo']);
        self::assertInstanceOf(FactoryTestDefMock::class, $s1);
        self::assertNull($s1->def);
        $s1 = Factory::mergeSeeds(new FactoryTestDefMock(), ['foo' => 'x']);
        self::assertInstanceOf(FactoryTestDefMock::class, $s1);
        self::assertSame(['foo' => 'x'], $s1->def);
    }

    public function testMerge8(): void
    {
        $s1 = Factory::mergeSeeds(['foo', null, 'arg'], []);
        self::assertSame(['foo', null, 'arg'], $s1);
    }

    public function testSeedMustBe(): void
    {
        $this->expectException(Exception::class);
        Factory::factory([], ['foo' => 'bar']);
    }

    public function testClassMayNotBeEmpty(): void
    {
        $this->expectException(\Error::class);
        Factory::factory([''], [FactoryTestDiMock::class, 'test']);
    }

    public function testMystBeDi(): void
    {
        $this->expectException(Exception::class);
        Factory::factory([FactoryTestMock::class, 'hello', 'foo' => 'bar', 'world']);
    }

    public function testMustHaveProperty(): void
    {
        $this->expectException(Exception::class);
        Factory::factory([FactoryTestDiMock::class, 'hello', 'xxx' => 'bar', 'world']);
    }

    public function testGiveClassFirst(): void
    {
        $this->expectException(\TypeError::class);
        Factory::factory(['foo' => 'bar'], new FactoryTestDiMock()); // @phpstan-ignore argument.type
    }

    public function testStringDefault(): void
    {
        $s1 = Factory::factory([FactoryTestDiMock::class], ['hello']);
        self::assertTrue($s1 instanceof FactoryTestDiMock);
        self::assertSame(['hello'], $s1->args);

        // also OK if it's not a DIContainer object
        $s1 = Factory::factory([FactoryTestMock::class], ['hello']);
        self::assertTrue($s1 instanceof FactoryTestMock);
        self::assertSame(['hello'], $s1->args);
    }

    /**
     * Cannot inject in non-DI.
     */
    public function testNonDiInject(): void
    {
        $this->expectException(Exception::class);
        Factory::factory([FactoryTestMock::class], ['foo' => 'hello']);
    }

    public function testPropertyMerging(): void
    {
        $s1 = Factory::factory(
            [FactoryTestDiMock::class, 'foo' => ['Button', 'icon' => 'red']],
            ['foo' => ['Label', 'red']]
        );

        self::assertSame(['Button', 'icon' => 'red'], $s1->foo);

        $s1->setDefaults(['foo' => ['Message', 'detail' => 'blah']]);

        self::assertSame(['Message', 'detail' => 'blah'], $s1->foo);
    }

    public function testFactory(): void
    {
        $m = new FactoryFactoryMock();

        // pass object
        $m1 = new FactoryFactoryMock();
        $m2 = Factory::factory($m1);
        self::assertSame($m1, $m2);

        // from array seed
        $m1 = Factory::factory([FactoryFactoryMock::class]);
        self::assertSame(FactoryFactoryMock::class, get_class($m1));

        // from string seed
        $this->expectException(Exception::class);
        Factory::factory(FactoryFactoryMock::class); // @phpstan-ignore argument.type
    }

    /**
     * Object factory definition must use ["class name", "x" => "y"] form.
     */
    public function testFactoryException1(): void
    {
        // wrong 1st parameter
        $this->expectException(Exception::class);
        $m = new FactoryFactoryMock();
        Factory::factory(['wrong_parameter' => 'qwerty']);
    }

    public function testFactoryParameters(): void
    {
        $m = new FactoryFactoryDiMock();

        // as class name
        $m1 = Factory::factory([FactoryFactoryMock::class]);
        self::assertSame(FactoryFactoryMock::class, get_class($m1));

        $m2 = Factory::factory([HookBreaker::class], ['ok']);
        self::assertSame(HookBreaker::class, get_class($m2));

        // as object
        $m2 = Factory::factory($m1);
        self::assertSame(FactoryFactoryMock::class, get_class($m2));

        // as class name with parameters
        $m1 = Factory::factory([FactoryFactoryDiMock::class], ['a' => 'XXX', 'b' => 'YYY']);
        self::assertSame('XXX', $m1->a);
        self::assertSame('YYY', $m1->b);
        self::assertNull($m1->c);

        $m1 = Factory::factory([FactoryFactoryDiMock::class], ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        self::assertSame('AAA', $m1->a);
        self::assertSame('YYY', $m1->b);
        self::assertSame('ZZZ', $m1->c);

        // as object with parameters
        $m1 = Factory::factory([FactoryFactoryDiMock::class]);
        $m2 = Factory::factory($m1, ['a' => 'XXX', 'b' => 'YYY']);
        self::assertSame('XXX', $m2->a);
        self::assertSame('YYY', $m2->b);
        self::assertNull($m2->c);

        $m1 = Factory::factory([FactoryFactoryDiMock::class]);
        $m2 = Factory::factory($m1, ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        self::assertSame('AAA', $m2->a);
        self::assertSame('YYY', $m2->b);
        self::assertSame('ZZZ', $m2->c);

        $m1 = Factory::factory([FactoryFactoryDiMock::class], ['a' => null, 'b' => 'YYY', 'c' => 'SSS']);
        $m2 = Factory::factory($m1, ['a' => 'XXX', 'b' => null, 'c' => 'ZZZ']);
        self::assertSame('XXX', $m2->a);
        self::assertSame('YYY', $m2->b);
        self::assertSame('ZZZ', $m2->c);

        // as object wrapped in array
        $this->expectException(Exception::class);
        Factory::factory([$m1]);
    }

    /**
     * Object factory cannot add not defined properties.
     * Receive as class name.
     */
    public function testFactoryParametersException1(): void
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryFactoryMock();
        Factory::factory([FactoryFactoryMock::class], ['not_exist' => 'test']);
    }

    /**
     * Object factory cannot add not defined properties.
     * Receive as object.
     */
    public function testFactoryParametersException2(): void
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryFactoryMock();
        $m1 = Factory::factory([FactoryFactoryMock::class]);
        Factory::factory($m1, ['not_exist' => 'test']);
    }
}

class FactoryTestMock
{
    /** @var array<mixed> */
    public $args;
    /** @var int|string|array<int, string> */
    public $foo;
    /** @var int|string */
    public $baz = 0;

    /**
     * @param mixed ...$args
     */
    public function __construct(...$args)
    {
        $this->args = $args;
    }
}

class FactoryTestDiMock extends FactoryTestMock
{
    use DiContainerTrait;
}

class FactoryTestViewMock extends FactoryTestMock
{
    use DiContainerTrait {
        setDefaults as private _setDefaults;
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return $this
     */
    public function setDefaults(array $properties, bool $passively = false)
    {
        if (array_key_exists('foo', $properties)) {
            if ($passively) {
                $this->foo = array_merge($properties['foo'], $this->foo);
            } else {
                $this->foo = array_merge($this->foo, $properties['foo']);
            }
            unset($properties['foo']);
        }

        return $this->_setDefaults($properties, $passively);
    }
}

class FactoryTestDefMock extends FactoryTestMock
{
    use DiContainerTrait {
        setDefaults as private _setDefaults;
    }

    /** @var array<string, mixed>|null */
    public $def;

    /**
     * @param array<string, mixed> $properties
     *
     * @return $this
     */
    public function setDefaults(array $properties, bool $passively = false)
    {
        $this->def = $properties;

        return $this;
    }
}

class FactoryFactoryMock
{
    public ?string $a = 'AAA';
    public ?string $b = 'BBB';
    public ?string $c = null;
}

class FactoryFactoryDiMock
{
    use DiContainerTrait;

    public ?string $a = 'AAA';
    public ?string $b = 'BBB';
    public ?string $c = null;
}
