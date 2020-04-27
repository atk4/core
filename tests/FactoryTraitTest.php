<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\AtkPhpunit;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\core\FactoryTrait;
use atk4\core\HookBreaker as HB;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class FactoryTraitTest extends AtkPhpunit\TestCase
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
        $this->assertSame($m1, $m2);

        // pass classname
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m1));
    }

    /**
     * Test normalizeClassName().
     */
    public function testNormalize()
    {
        $m = new FactoryMock();

        // parameter as simple string
        $class = $m->normalizeClassName('MyClass');
        $this->assertSame('MyClass', $class);

        // parameter as namespaced string
        $class = $m->normalizeClassName('a\b\MyClass');
        $this->assertSame('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b\MyClass');
        $this->assertSame('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b/MyClass', 'Prefix');
        $this->assertSame('Prefix\a\b\MyClass', $class);

        $class = $m->normalizeClassName('/a/b/MyClass', 'Prefix');
        $this->assertSame('\a\b\MyClass', $class);

        $class = $m->normalizeClassName('\a\b\MyClass', 'Prefix');
        $this->assertSame('\a\b\MyClass', $class);

        $class = $m->normalizeClassName('a\b\MyClass', 'Prefix');
        $this->assertSame('a\b\MyClass', $class);

        $class = $m->normalizeClassName(\atk\data\Persistence::class, 'Prefix');
        $this->assertSame('atk\data\Persistence', $class);

        $class = $m->normalizeClassName(HB::class);
        $this->assertSame('atk4\core\HookBreaker', $class);

        $class = $m->normalizeClassName(\Datetime::class, 'Prefix');
        $this->assertSame('Prefix\Datetime', $class);

        $class = $m->normalizeClassName('.Datetime', 'Prefix');
        $this->assertSame('Prefix\Datetime', $class);

        $class = $m->normalizeClassName('.Date\Time', 'Prefix');
        $this->assertSame('Prefix\Date\Time', $class);

        // With Application Prefixing
        $m = new FactoryAppScopeMock();
        $m->app = new FactoryTestAppMock();
        $class = $m->normalizeClassName('MyClass', 'atk4\test');
        $this->assertSame('atk4\mytest\MyClass', $class);
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
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(\atk4\core\tests\FactoryMock::class);
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(FactoryMock::class);
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m1));

        $m1 = $m->factory(HB::class, ['ok']);
        $this->assertSame('atk4\core\HookBreaker', get_class($m1));

        $m1 = $m->factory(['atk4\core\tests\FactoryMock']);
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m1));

        // as object
        $m2 = $m->factory($m1);
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m2));

        $m2 = $m->factory([$m1]);
        $this->assertSame('atk4\core\tests\FactoryMock', get_class($m2));

        // as class name with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertNull($m1->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertSame('AAA', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertSame('ZZZ', $m1->c);

        // as object with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertNull($m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertSame('AAA', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('ZZZ', $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'SSS']);
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => null, 'c' => 'ZZZ']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('ZZZ', $m2->c);
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
    public function normalizeClassNameApp(string $name, string $prefix = null): ?string
    {
        if ($prefix === 'atk4\test') {
            return 'atk4\mytest\\' . $name;
        }
    }
}
// @codingStandardsIgnoreEnd
