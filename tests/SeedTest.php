<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\core\FactoryTrait;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class SeedTest extends AtkPhpunit\TestCase
{
    use FactoryTrait;

    public function testMerge1()
    {
        // string become array
        $this->assertSame(
            ['hello'],
            $this->mergeSeeds(['hello'], null)
        );

        // left-most value is used
        $this->assertSame(
            ['one'],
            $this->mergeSeeds(['one'], ['two'], ['three'], ['four'])
        );

        // nulls are ignored
        $this->assertSame(
            ['two'],
            $this->mergeSeeds(null, ['two'], ['three'], ['four'])
        );

        // object takes precedence
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds(null, ['two'], $o, ['four'])
        );

        // if more than one object, leftmost is returned
        $o1 = new SeedDITestMock();
        $o2 = new SeedDITestMock();
        $this->assertSame(
            $o2,
            $this->mergeSeeds(null, ['two'], $o2, $o1, ['four'])
        );
    }

    public function testMerge2()
    {
        // array argument merging
        $this->assertSame(
            ['a1', 'a2'],
            $this->mergeSeeds(['a1', 'a2'], null, ['b1', 'b2'])
        );

        // nulls are ignored
        $this->assertSame(
            ['b1', 'a2', 'c3'],
            $this->mergeSeeds([null, 'a2', null], ['b1'], ['c1', null, 'c3'])
        );

        // object takes precedence
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds(['a1'], $o)
        );

        // is object is wrapped in array - we dont care
        $o = new SeedDITestMock();
        $this->assertSame(
            ['b1', 'a2', 'c3'],
            $this->mergeSeeds([null, 'a2', null], ['b1'], ['c1', null, 'c3'], [$o])
        );

        // we will still return it
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds([null, 'a2', null], [null, null, 'c3'], [$o])[0]
        );

        // but constructor arguments (except silently ignored class name)
        // for already instanced object are not valid
        $o = new SeedDITestMock();
        $this->expectException(Exception::class);
        $this->mergeSeeds(['a1', 'a2'], $o);
    }

    public function testMerge3()
    {
        // key/value support
        $this->assertSame(
            ['a' => 1],
            $this->mergeSeeds(['a' => 1], ['a' => 2])
        );

        // values has no special treatment
        $this->assertSame(
            ['a' => [1]],
            $this->mergeSeeds(['a' => [1]], ['a' => 2])
        );

        // object is injected with values
        $o = new SeedDITestMock();
        $oo = $this->mergeSeeds(['foo' => 1], $o);

        $this->assertSame($o, $oo);
        $this->assertSame(1, $oo->foo);

        // even it already has value
        $o = new SeedDITestMock();
        $o->foo = 5;
        $oo = $this->mergeSeeds(['foo' => 1], $o);

        $this->assertSame($o, $oo);
        $this->assertSame(1, $oo->foo);

        // but this way existing value is respected
        $o = new SeedDITestMock();
        $o->foo = 5;
        $oo = $this->mergeSeeds($o, ['foo' => 1]);

        $this->assertSame($o, $oo);
        $this->assertSame(5, $oo->foo);
    }

    public function testMerge4()
    {
        // array values don't overwrite but rather merge
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo' => ['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertSame(['red', 'green'], $oo->foo);

        // still we don't care if they are to the right of the object
        $o = new SeedDITestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds($o, ['foo' => ['green']]);

        $this->assertSame($o, $oo);
        $this->assertSame(['red'], $oo->foo);
    }

    public function testMerge5()
    {
        // works even if more arguments present
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo' => ['xx']], ['foo' => ['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertSame(['red', 'green', 'xx'], $oo->foo);

        // also without arrays
        $o = new SeedDITestMock();
        $o->foo = 'red';
        $oo = $this->mergeSeeds(['foo' => 'xx'], ['foo' => 'green'], $o, ['foo' => 5]);

        $this->assertSame($o, $oo);
        $this->assertSame('xx', $oo->foo);
    }

    public function testMerge5b()
    {
        // and even if multiple objects are found
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $o2 = new ViewTestMock();
        $o2->foo = ['yellow'];
        $oo = $this->mergeSeeds(['foo' => ['xx']], $o, ['foo' => ['green']], $o2, ['foo' => ['cyan']]);

        $this->assertSame($o, $oo);
        $this->assertSame(['red', 'xx'], $oo->foo);
    }

    public function testMerge6()
    {
        $oo = $this->mergeSeeds(['4' => 'four'], ['5' => 'five']);
        $this->assertSame(['4' => 'four', '5' => 'five'], $oo);

        $oo = $this->mergeSeeds(['4' => ['four']], ['5' => ['five']]);
        $this->assertSame(['4' => ['four'], '5' => ['five']], $oo);

        $oo = $this->mergeSeeds(['4' => 'four'], ['5' => 'five'], ['6' => 'six']);
        $this->assertSame(['4' => 'four', '5' => 'five', '6' => 'six'], $oo);

        $oo = $this->mergeSeeds(['x' => ['four']], ['x' => ['five']]);
        $this->assertSame(['x' => ['four']], $oo);

        $oo = $this->mergeSeeds(['4' => ['four']], ['4' => ['five']]);
        $this->assertSame(['4' => ['four']], $oo);

        $oo = $this->mergeSeeds(['4' => ['200']], ['4' => ['201']]);
        $this->assertSame(['4' => ['200']], $oo);
    }

    public function testMergeFail1()
    {
        // works even if more arguments present
        $this->expectException(Exception::class);
        $o = new SeedTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds($o, ['foo' => 5]);

        $this->assertSame($o, $oo);
        $this->assertSame(['red', 'green', 'xx'], $oo->foo);
    }

    public function testMergeFail2()
    {
        // works even if more arguments present
        $this->expectException(Exception::class);
        $o = new SeedTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo' => ['xx']], ['foo' => ['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertSame(['red', 'green', 'xx'], $oo->foo);
    }

    public function testBasic()
    {
        $s1 = $this->factory([SeedTestMock::class]);
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock());
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock('hello', 'world'));
        $this->assertSame(['hello', 'world'], $s1->args);

        $s1 = $this->factory(new SeedTestMock(null, 'world'));
        $this->assertSame([null, 'world'], $s1->args);
    }

    public function testInjection()
    {
        $s1 = $this->factory(new SeedDITestMock(), null);
        $this->assertNotSame('bar', $s1->foo);

        $s1 = $this->factory(new SeedDITestMock(), ['foo' => 'bar']);
        $this->assertSame('bar', $s1->foo);
    }

    public function testArguments()
    {
        /*
        $s1 = $this->factory([SeedTestMock::class, 'hello']);
        $this->assertEquals(['hello'], $s1->args);

        $s1 = $this->factory([SeedTestMock::class, 'hello', 'world']);
        $this->assertEquals(['hello', 'world'], $s1->args);
         */

        $s1 = $this->factory([SeedTestMock::class, null, 'world']);
        $this->assertSame([null, 'world'], $s1->args);

        $s1 = $this->factory([SeedDITestMock::class, 'hello', 'foo' => 'bar', 'world']);
        $this->assertSame(['hello', 'world'], $s1->args);
        $this->assertSame('bar', $s1->foo);
    }

    public function testDefaults()
    {
        $s1 = $this->factory([SeedDITestMock::class, 'hello', 'foo' => 'bar', 'world'], ['more', 'baz' => '', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['hello', 'world', 'args'], $s1->args);
        $this->assertSame('bar', $s1->foo);
        $this->assertSame('', $s1->baz);

        $s1->setDefaults([]);
    }

    public function testNull()
    {
        $s1 = $this->factory([SeedDITestMock::class, 'foo' => null, null, 'world'], ['more', 'foo' => 'bar', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['more', 'world', 'args'], $s1->args);
        $this->assertSame('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds([SeedDITestMock::class, 'foo' => null, null, 'world'], [SeedTestMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['more', 'world', 'args'], $s1->args);
        $this->assertSame('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds([null, 'foo' => null, null, 'world'], [SeedDITestMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['more', 'world', 'args'], $s1->args);
        $this->assertSame('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds(null, [SeedDITestMock::class, 'more', 'foo' => 'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['more', 'more', 'args'], $s1->args);
        $this->assertSame('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds([], [SeedDITestMock::class, 'test']));
        $this->assertSame(['test'], $s1->args);
    }

    public function testDefaultsObject()
    {
        $s1 = $this->factory([new SeedDITestMock(), 'foo' => 'bar'], ['baz' => '', 'foo' => 'default']);
        $this->assertSame('bar', $s1->foo);
        $this->assertSame('', $s1->baz);
    }

    public function testMerge()
    {
        $s1 = $this->factory([new SeedDITestMock(), 'foo' => ['red']], ['foo' => ['big'], 'foo' => 'default']);
        $this->assertSame(['red'], $s1->foo);

        $o = new ViewTestMock();
        $o->foo = ['xx'];
        $s1 = $this->factory([$o, 'foo' => ['red']], ['foo' => ['big'], 'foo' => 'default']);
        $this->assertSame(['xx', 'red'], $s1->foo);

        $s1 = $this->factory([SeedDITestMock::class, 'hello', 'world'], ['more', 'more', 'args']);
        $this->assertSame(['hello', 'world', 'args'], $s1->args);

        $s1 = $this->factory([SeedDITestMock::class, null, 'world'], ['more', 'more', 'args']);
        $this->assertSame(['more', 'world', 'args'], $s1->args);

        $s1 = $this->factory([new SeedDITestMock('x', 'y'), null, 'bar'], ['foo', 'baz']);
        $this->assertSame(['x', 'y'], $s1->args);
    }

    public function testMerge7()
    {
        $s1 = $this->mergeSeeds(new SeedDefTestMock(), ['foo']);
        $this->assertNull($s1->def);
    }

    public function testMerge8()
    {
        $s1 = $this->mergeSeeds(['foo', null, 'arg'], []);
        $this->assertSame(['foo', null, 'arg'], $s1);
    }

    public function testSeedMustBe()
    {
        $this->expectException(Exception::class);
        $s1 = $this->factory([], ['foo' => 'bar']);
    }

    public function testClassMayNotBeEmpty()
    {
        $this->expectException(\Error::class);
        $s1 = $this->factory([''], [SeedDITestMock::class, 'test']);
    }

    public function testMystBeDI()
    {
        $this->expectException(Exception::class);
        $s1 = $this->factory([SeedTestMock::class, 'hello', 'foo' => 'bar', 'world']);
    }

    public function testMustHaveProperty()
    {
        $this->expectException(Exception::class);
        $s1 = $this->factory([SeedDITestMock::class, 'hello', 'xxx' => 'bar', 'world']);
    }

    public function testGiveClassFirst()
    {
        $this->expectException(Exception::class);
        $s1 = $this->factory(['foo' => 'bar'], new SeedDITestMock());
    }

    public function testStringDefault()
    {
        $s1 = $this->factory([SeedDITestMock::class], ['hello']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['hello'], $s1->args);

        // also OK if it's not a DIContainer object
        $s1 = $this->factory([SeedTestMock::class], ['hello']);
        $this->assertTrue($s1 instanceof SeedTestMock);
        $this->assertSame(['hello'], $s1->args);
    }

    /**
     * Cannot inject in non-DI.
     */
    public function testNonDIInject()
    {
        $this->expectException(Exception::class);
        $s1 = $this->factory([SeedTestMock::class], ['foo' => 'hello']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertSame(['hello'], $s1->args);
    }

    /**
     * Test seed property merging.
     */
    public function testPropertyMerging()
    {
        $s1 = $this->factory(
            [SeedDITestMock::class, 'foo' => ['Button', 'icon' => 'red']],
            ['foo' => ['Label', 'red']]
        );

        $this->assertSame(['Button', 'icon' => 'red'], $s1->foo);

        $s1->setDefaults(['foo' => ['Message', 'detail' => 'blah']]);

        $this->assertSame(['Message', 'detail' => 'blah'], $s1->foo);
    }
}

class SeedTestMock
{
    public $args;
    public $foo;
    public $baz = 0;

    public function __construct(...$args)
    {
        $this->args = $args;
    }
}

class SeedDITestMock extends SeedTestMock
{
    use DIContainerTrait;
}

class ViewTestMock extends SeedTestMock
{
    use DIContainerTrait {
        setDefaults as _setDefaults;
    }
    public $def;

    public function setDefaults(array $properties, bool $passively = false)
    {
        if ($properties['foo']) {
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

class SeedDefTestMock extends SeedTestMock
{
    use DIContainerTrait {
        setDefaults as _setDefaults;
    }
    public $def;

    public function setDefaults(array $properties, bool $passively = false)
    {
        $this->def = $properties;
    }
}
