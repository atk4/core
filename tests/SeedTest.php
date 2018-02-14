<?php

namespace atk4\core\tests;

use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class SeedTest extends \PHPUnit_Framework_TestCase
{
    /*
     * Test constructor.
     */
    use FactoryTrait;

    public function testMerge1()
    {
        // string become array
        $this->assertEquals(
            ['hello'],
            $this->mergeSeeds('hello', null)
        );

        // left-most value is used
        $this->assertEquals(
            ['one'],
            $this->mergeSeeds('one', 'two', 'three', 'four')
        );

        // nulls are ignored
        $this->assertEquals(
            ['two'],
            $this->mergeSeeds(null, 'two', 'three', 'four')
        );

        // object takes precedence
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds(null, 'two', $o, 'four')
        );

        // if more than one object, leftmost is returned
        $o1 = new SeedDITestMock();
        $o2 = new SeedDITestMock();
        $this->assertSame(
            $o2,
            $this->mergeSeeds(null, 'two', $o2, $o1, 'four')
        );
    }

    public function testMerge2()
    {
        // array argument merging
        $this->assertEquals(
            ['a1', 'a2'],
            $this->mergeSeeds(['a1', 'a2'], null, ['b1', 'b2'])
        );

        // nulls are ignored
        $this->assertEquals(
            ['b1', 'a2', 'c3'],
            $this->mergeSeeds([null, 'a2', null], 'b1', ['c1', null, 'c3'])
        );

        // object takes precedence
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds([null, 'a2', null], 'b1', ['c1', null, 'c3'], $o)
        );

        // is object is wrapped in array - we dont care
        $o = new SeedDITestMock();
        $this->assertEquals(
            ['b1', 'a2', 'c3'],
            $this->mergeSeeds([null, 'a2', null], 'b1', ['c1', null, 'c3'], [$o])
        );

        // we will still return it
        $o = new SeedDITestMock();
        $this->assertSame(
            $o,
            $this->mergeSeeds([null, 'a2', null], [null, null, 'c3'], [$o])[0]
        );
    }

    public function testMerge3()
    {
        // key/value support
        $this->assertEquals(
            ['a'=>1],
            $this->mergeSeeds(['a'=>1], ['a'=>2])
        );

        // values has no special treatment
        $this->assertEquals(
            ['a'=>[1]],
            $this->mergeSeeds(['a'=>[1]], ['a'=>2])
        );

        // object is injected with values
        $o = new SeedDITestMock();
        $oo = $this->mergeSeeds(['foo'=>1], $o);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, 1);

        // even it already has value
        $o = new SeedDITestMock();
        $o->foo = 5;
        $oo = $this->mergeSeeds(['foo'=>1], $o);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, 1);

        // but this way existing value is respected
        $o = new SeedDITestMock();
        $o->foo = 5;
        $oo = $this->mergeSeeds($o, ['foo'=>1]);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, 5);
    }

    public function testMerge4()
    {
        // array values don't overwrite but rather merge
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo'=>['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, ['red', 'green']);

        // still we don't care if they are to the right of the object
        $o = new SeedDITestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds($o, ['foo'=>['green']]);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, ['red']);
    }

    public function testMerge5()
    {
        // works even if more arguments present
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo'=>['xx']], ['foo'=>['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, ['red', 'green', 'xx']);

        // also without arrays
        $o = new SeedDITestMock();
        $o->foo = 'red';
        $oo = $this->mergeSeeds(['foo'=>'xx'], ['foo'=>'green'], $o, ['foo'=>5]);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, 'xx');
    }

    public function testMerge5b()
    {
        // and even if multiple objects are found
        $o = new ViewTestMock();
        $o->foo = ['red'];
        $o2 = new ViewTestMock();
        $o2->foo = ['yellow'];
        $oo = $this->mergeSeeds(['foo'=>['xx']], $o, ['foo'=>['green']], $o2, ['foo'=>['cyan']]);

        $this->assertSame($o, $oo);
        $this->assertEquals(['red', 'xx'], $oo->foo);
    }

    public function testMerge6()
    {
        $oo = $this->mergeSeeds(['4'=>'four'], ['5'=>'five']);
        $this->assertEquals($oo, ['4'=>'four', '5'=>'five']);

        $oo = $this->mergeSeeds(['4'=>['four']], ['5'=>['five']]);
        $this->assertEquals($oo, ['4'=>['four'], '5'=>['five']]);

        $oo = $this->mergeSeeds(['x'=>['four']], ['x'=>['five']]);
        $this->assertEquals($oo, ['x'=>['four']]);

        $oo = $this->mergeSeeds(['4'=>['four']], ['4'=>['five']]);
        $this->assertEquals($oo, ['4'=>['four']]);

        $oo = $this->mergeSeeds(['4'=>['200']], ['4'=>['201']]);
        $this->assertEquals($oo, ['4'=>['200']]);
    }

    /**
     * @expectedException     Exception
     */
    public function testMergeFail1()
    {
        // works even if more arguments present
        $o = new SeedTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds($o, ['foo'=>5]);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, ['red', 'green', 'xx']);
    }

    /**
     * @expectedException     Exception
     */
    public function testMergeFail2()
    {
        // works even if more arguments present
        $o = new SeedTestMock();
        $o->foo = ['red'];
        $oo = $this->mergeSeeds(['foo'=>['xx']], ['foo'=>['green']], $o);

        $this->assertSame($o, $oo);
        $this->assertEquals($oo->foo, ['red', 'green', 'xx']);
    }

    public function testBasic()
    {
        $s1 = $this->factory('atk4/core/tests/SeedTestMock');
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock());
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock('hello', 'world'));
        $this->assertEquals(['hello', 'world'], $s1->args);

        $s1 = $this->factory(new SeedTestMock(null, 'world'));
        $this->assertEquals([null, 'world'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedTestMock']);
        $this->assertEmpty($s1->args);
    }

    public function testInjection()
    {
        $s1 = $this->factory(new SeedDITestMock(), null);
        $this->assertNotEquals('bar', $s1->foo);

        $s1 = $this->factory(new SeedDITestMock(), ['foo'=>'bar']);
        $this->assertEquals('bar', $s1->foo);
    }

    public function testArguments()
    {
        /*
        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', 'hello']);
        $this->assertEquals(['hello'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', 'hello', 'world']);
        $this->assertEquals(['hello', 'world'], $s1->args);
         */

        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', null, 'world']);
        $this->assertEquals([null, 'world'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'foo'=>'bar', 'world']);
        $this->assertEquals(['hello', 'world'], $s1->args);
        $this->assertEquals('bar', $s1->foo);
    }

    public function testPrefix()
    {
        // prefix could be fully specified (global)
        $s1 = $this->factory('SeedTestMock', ['hello'], '/atk4/core/tests');
        $this->assertEquals(['hello'], $s1->args);

        // specifying prefix yourself will override, but only if you start with slash
        $s1 = $this->factory('/atk4/core/tests/SeedTestMock', ['hello'], '/atk4/core/tests');
        $this->assertEquals(['hello'], $s1->args);

        // without slash, prefixes add up
        $s1 = $this->factory('tests/SeedTestMock', ['hello'], '/atk4/core');
        $this->assertEquals(['hello'], $s1->args);
    }

    public function testDefaults()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'foo'=>'bar', 'world'], ['more', 'baz'=>'', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['hello', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);
        $this->assertEquals('', $s1->baz);

        $s1->setDefaults(null);
    }

    public function testNull()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'foo'=>null, null, 'world'], ['more', 'foo'=>'bar', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds(['atk4/core/tests/SeedDITestMock', 'foo'=>null, null, 'world'], ['atk4/core/tests/SeedTestMock', 'more', 'foo'=>'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds([null, 'foo'=>null, null, 'world'], ['atk4/core/tests/SeedDITestMock', 'more', 'foo'=>'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds(null, ['atk4/core/tests/SeedDITestMock', 'more', 'foo'=>'bar', 'more', 'args']));
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'more', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory($this->mergeSeeds([], ['atk4/core/tests/SeedDITestMock', 'test']));
        $this->assertEquals(['test'], $s1->args);
    }

    public function testDefaultsObject()
    {
        $s1 = $this->factory([new SeedDITestMock(), 'foo'=>'bar'], ['baz'=>'', 'foo'=>'default']);
        $this->assertEquals('bar', $s1->foo);
        $this->assertEquals('', $s1->baz);
    }

    public function testMerge()
    {
        $s1 = $this->factory([new SeedDITestMock(), 'foo'=>['red']], ['foo'=>['big'], 'foo'=>'default']);
        $this->assertEquals(['red'], $s1->foo);

        $o = new ViewTestMock();
        $o->foo = ['xx'];
        $s1 = $this->factory([$o, 'foo'=>['red']], ['foo'=>['big'], 'foo'=>'default']);
        $this->assertEquals(['xx', 'red'], $s1->foo);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'world'], ['more', 'more', 'args']);
        $this->assertEquals(['hello', 'world', 'args'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', null, 'world'], ['more', 'more', 'args']);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);

        $s1 = $this->factory([new SeedDITestMock('x', 'y'), null, 'bar'], ['foo', 'baz']);
        $this->assertEquals(['x', 'y'], $s1->args);
    }

    public function testMerge7()
    {
        $s1 = $this->mergeSeeds(new SeedDefTestMock(), ['foo']);
        $this->assertEquals(null, $s1->def);
    }

    public function testMerge8()
    {
        $s1 = $this->mergeSeeds(['foo', null, 'arg'], []);
        $this->assertEquals(['foo', null, 'arg'], $s1);
    }

    /**
     * @expectedException     Exception
     */
    public function testSeedMustBe()
    {
        $s1 = $this->factory([], ['foo' => 'bar']);
    }

    /**
     * @expectedException     Exception
     */
    public function testClassMayNotBeEmpty()
    {
        $s1 = $this->factory([''], ['atk4/core/tests/SeedDITestMock', 'test']);
        $this->assertEquals(['test'], $s1->args);
    }

    /**
     * @expectedException     Exception
     */
    public function testMystBeDI()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', 'hello', 'foo'=>'bar', 'world']);
    }

    /**
     * @expectedException     Exception
     */
    public function testMustHaveProperty()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'xxx'=>'bar', 'world']);
    }

    public function testGiveClassFirst()
    {
        $s1 = $this->factory(['foo'=>'bar'], new SeedDITestMock());
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals('bar', $s1->foo);
    }

    public function testStringDefault()
    {
        $s1 = $this->factory('atk4/core/tests/SeedDITestMock', 'hello');
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['hello'], $s1->args);

        // also OK if it's not a DIContainer object
        $s1 = $this->factory('atk4/core/tests/SeedTestMock', 'hello');
        $this->assertTrue($s1 instanceof SeedTestMock);
        $this->assertEquals(['hello'], $s1->args);
    }

    /**
     * Cannot inject in non-DI.
     *
     * @expectedException     Exception
     */
    public function testNonDIInject()
    {
        $s1 = $this->factory('atk4/core/tests/SeedTestMock', ['foo'=>'hello']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['hello'], $s1->args);
    }

    /**
     * Test seed property merging.
     */
    public function testPropertyMerging()
    {
        $s1 = $this->factory(
            ['atk4/core/tests/SeedDITestMock', 'foo'=>['Button', 'icon'=>'red']],
            ['foo'=> ['Label', 'red']]);

        $this->assertEquals(['Button', 'icon'=>'red'], $s1->foo);

        $s1->setDefaults(['foo'=>['Message', 'detail'=>'blah']]);

        $this->assertEquals(['Message', 'detail'=>'blah'], $s1->foo);
    }
}

class SeedTestMock
{
    public $args = null;
    public $foo = null;
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
    public $def = null;

    public function setDefaults($properties = [], $passively = false)
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
    public $def = null;

    public function setDefaults($def, $passively = false)
    {
        $this->def = $def;
    }
}

class SeedAppPrefixMock
{
    public function normalizeClassNameApp($name, $prefix)
    {
        var_dump($name, $prefix);
    }
}
