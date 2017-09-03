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

    public function testDefaults()
    {
        $s1 = $this->factory(['atk4/core/tests/SeedDITestMock', 'hello', 'foo'=>'bar', 'world'], ['more', 'baz'=>'', 'args']);
        $this->assertEquals(['hello', 'world', 'more', 'args'], $s1->args);
        $this->assertEquals('bar', $s1->foo);
        $this->assertEquals('', $s1->baz);
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

    /**
     * @expectedException     Exception
     */
    public function testGiveClassFirst()
    {
        $s1 = $this->factory(['foo'=>'bar'], ['atk4/core/tests/SeedDITestMock']);
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
