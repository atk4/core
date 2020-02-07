<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\core\FactoryTrait;
use atk4\core\HookBreaker as HB;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class FactoryTraitTest extends TestCase
{
    /**
     * Test factory().
     */
    public function testFactory()
    {
        $m = new FactoryMock();

        // pass object
        $m1 = new FactoryMock();
        $m2 = $m->factory($m1);
        $this->assertEquals($m1, $m2);

        // pass classname
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));
    }

    /**
     * Test normalizeClassName().
     */
    public function testNormalize()
    {
        $m = new FactoryMock();

        // parameter as simple string
        $class = $m->normalizeClassName('MyClass');
        $this->assertEquals('MyClass', $class);

        // parameter as namespaced string
        $class = $m->normalizeClassName('a\b\MyClass');
        $this->assertEquals('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b\MyClass');
        $this->assertEquals('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b/MyClass', 'Prefix');
        $this->assertEquals('Prefix\a\b\MyClass', $class);

        $class = $m->normalizeClassName('/a/b/MyClass', 'Prefix');
        $this->assertEquals('\a\b\MyClass', $class);

        $class = $m->normalizeClassName('\a\b\MyClass', 'Prefix');
        $this->assertEquals('\a\b\MyClass', $class);

        $class = $m->normalizeClassName('a\b\MyClass', 'Prefix');
        $this->assertEquals('a\b\MyClass', $class);

        $class = $m->normalizeClassName(\atk\data\Persistence::class, 'Prefix');
        $this->assertEquals('atk\data\Persistence', $class);

        $class = $m->normalizeClassName(HB::class);
        $this->assertEquals('atk4\core\HookBreaker', $class);

        $class = $m->normalizeClassName(\Datetime::class, 'Prefix');
        $this->assertEquals('Prefix\Datetime', $class);

        $class = $m->normalizeClassName('.Datetime', 'Prefix');
        $this->assertEquals('Prefix\Datetime', $class);

        $class = $m->normalizeClassName('.Date\Time', 'Prefix');
        $this->assertEquals('Prefix\Date\Time', $class);

        // With Application Prefixing
        $m = new FactoryAppScopeMock();
        $m->app = new FactoryTestAppMock();
        $class = $m->normalizeClassName('MyClass', 'atk4\test');
        $this->assertEquals('atk4\mytest\MyClass', $class);
    }

    /**
     * Object factory definition must use ["class name", "x"=>"y"] form.
     */
    public function testException1()
    {
        // wrong 1st parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m->factory(['wrong_parameter' => 'qwerty']);
    }

    /**
     * Test factory parameters.
     */
    public function testParameters()
    {
        $m = new FactoryDIMock();

        // as class name
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(\atk4\core\tests\FactoryMock::class);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(FactoryMock::class);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(HB::class, ['ok']);
        $this->assertEquals('atk4\core\HookBreaker', get_class($m1));

        $m1 = $m->factory(['atk4\core\tests\FactoryMock']);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));

        // as object
        $m2 = $m->factory($m1);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m2));

        $m2 = $m->factory([$m1]);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m2));

        // as class name with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertEquals('XXX', $m1->a);
        $this->assertEquals('YYY', $m1->b);
        $this->assertEquals(null, $m1->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertEquals('AAA', $m1->a);
        $this->assertEquals('YYY', $m1->b);
        $this->assertEquals('ZZZ', $m1->c);

        // as object with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertEquals('XXX', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals(null, $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertEquals('AAA', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals('ZZZ', $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'SSS']);
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => null, 'c' => 'ZZZ']);
        $this->assertEquals('XXX', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals('ZZZ', $m2->c);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as class name.
     */
    public function testParametersException1()
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock', ['not_exist' => 'test']);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as object.
     */
    public function testParametersException2()
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $m2 = $m->factory($m1, ['not_exist' => 'test']);
    }
}

// @codingStandardsIgnoreStart
class FactoryMock
{
    use FactoryTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
class FactoryDIMock
{
    use FactoryTrait;
    use DIContainerTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
class FactoryAppScopeMock
{
    use AppScopeTrait;
    use FactoryTrait;
}

class FactoryTestAppMock
{
    public function normalizeClassNameApp($name, $prefix)
    {
        if ($prefix == 'atk4\test') {
            return 'atk4\mytest\\'.$name;
        }
    }
}
// @codingStandardsIgnoreEnd
