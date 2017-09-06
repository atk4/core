<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class FactoryTraitTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('Prefix\a\b\MyClass', $class);

        // With Application Prefixing
        $m = new FactoryAppScopeMock();
        $m->app = new FactoryTestAppMock();
        $class = $m->normalizeClassName('MyClass', 'model');
        $this->assertEquals('atk4\test\model\MyClass', $class);

        $class = $m->normalizeClassName('/MyClass', 'model');
        $this->assertEquals('\MyClass', $class);

        $class = $m->normalizeClassName('MyClass', '/model');
        $this->assertEquals('\model\MyClass', $class);

        $class = $m->normalizeClassName('MyClass', null);
        $this->assertEquals('atk4\test\MyClass', $class);

        $class = $m->normalizeClassName(null, null);
        $this->assertEquals('atk4\test\View', $class);
    }

    /**
     * Object factory definition must use ["class name", "x"=>"y"] form.
     *
     * @expectedException     Exception
     */
    public function testException1()
    {
        // wrong 1st parameter
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

        $m1 = $m->factory(['atk4\core\tests\FactoryMock']);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m1));

        // as object
        $m2 = $m->factory($m1);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m2));

        $m2 = $m->factory([$m1]);
        $this->assertEquals('atk4\core\tests\FactoryMock', get_class($m2));

        // as class name with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a'=>'XXX', 'b'=>'YYY']);
        $this->assertEquals('XXX', $m1->a);
        $this->assertEquals('YYY', $m1->b);
        $this->assertEquals(null, $m1->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a'=>null, 'b'=>'YYY', 'c'=>'ZZZ']);
        $this->assertEquals('AAA', $m1->a);
        $this->assertEquals('YYY', $m1->b);
        $this->assertEquals('ZZZ', $m1->c);

        // as object with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a'=>'XXX', 'b'=>'YYY']);
        $this->assertEquals('XXX', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals(null, $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a'=>null, 'b'=>'YYY', 'c'=>'ZZZ']);
        $this->assertEquals('AAA', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals('ZZZ', $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a'=>null, 'b'=>'YYY', 'c'=>'SSS']);
        $m2 = $m->factory($m1, ['a'=>'XXX', 'b'=>null, 'c'=>'ZZZ']);
        $this->assertEquals('XXX', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals('ZZZ', $m2->c);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as class name.
     *
     * @expectedException     Exception
     */
    public function testParametersException1()
    {
        // wrong property in 2nd parameter
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock', ['not_exist'=>'test']);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as object.
     *
     * @expectedException     Exception
     */
    public function testParametersException2()
    {
        // wrong property in 2nd parameter
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $m2 = $m->factory($m1, ['not_exist'=>'test']);
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
    public function normalizeClassNameApp($name)
    {
        if (!$name) {
            $name = 'View';
        }

        return 'atk4\test\\'.$name;
    }
}
// @codingStandardsIgnoreEnd
