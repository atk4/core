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

    public function testBasic()
    {
        $s1 = $this->factory('atk4/core/tests/SeedTestMock');
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock());
        $this->assertEmpty($s1->args);

        $s1 = $this->factory(new SeedTestMock('hello', 'world'));
        $this->assertEquals(['hello', 'world'], $s1->args);

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
        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', 'hello']);
        $this->assertEquals(['hello'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedTestMock', 'hello', 'world']);
        $this->assertEquals(['hello', 'world'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'foo'=>'bar', 'world']);
        $this->assertEquals(['hello', 'world'], $s1->args);
        $this->assertEquals('bar', $s1->foo);
    }

    public function testPrefix()
    {
        // prefix could be fully specified (global)
        $s1 = $this->factory('SeedTestMock', [null, 'hello'], '/atk4/core/tests');
        $this->assertEquals(['hello'], $s1->args);

        // specifying prefix yourself will override, but only if you start with slash
        $s1 = $this->factory('/atk4/core/tests/SeedTestMock', [null, 'hello'], '/atk4/core/tests');
        $this->assertEquals(['hello'], $s1->args);

        // without slash, prefixes add up
        $s1 = $this->factory('tests/SeedTestMock', [null, 'hello'], '/atk4/core');
        $this->assertEquals(['hello'], $s1->args);
    }

    public function testDefaults()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'foo'=>'bar', 'world'], ['atk4/core/tests/SeedTestMock', 'more', 'baz'=>'', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['hello', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);
        $this->assertEquals('', $s1->baz);

        $s1->setDefaults(null);
    }

    public function testNull()
    {
        $s1 = $this->factory([null, 'foo'=>null, null, 'world'], ['atk4/core/tests/SeedDITestMock', 'more', 'foo'=>'bar', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory(null, ['atk4/core/tests/SeedDITestMock', 'more', 'foo'=>'bar', 'more', 'args']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
        $this->assertEquals(['more', 'more', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);

        $s1 = $this->factory([], ['atk4/core/tests/SeedDITestMock', 'test']);
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

        $o = new SeedDITestMock();
        $o->foo = ['xx'];
        $s1 = $this->factory([$o, 'foo'=>['red']], ['foo'=>['big'], 'foo'=>'default']);
        $this->assertEquals(['xx', 'red'], $s1->foo);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'world'], [null, 'more', 'more', 'args']);
        $this->assertEquals(['hello', 'world', 'args'], $s1->args);

        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', null, 'world'], [null, 'more', 'more', 'args']);
        $this->assertEquals(['more', 'world', 'args'], $s1->args);

        $s1 = $this->factory([new SeedDITestMock('x', 'y'), null, 'bar'], [null, 'foo', 'baz']);
        $this->assertEquals(['x', 'y'], $s1->args);
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
        $s1 = $this->factory(['foo'=>'bar'], ['atk4/core/tests/SeedDITestMock']);
        $this->assertTrue($s1 instanceof SeedDITestMock);
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

class SeedAppPrefixMock
{
    public function normalizeClassNameApp($name, $prefix)
    {
        var_dump($name, $prefix);
    }
}
