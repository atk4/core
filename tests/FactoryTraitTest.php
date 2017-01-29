<?php

namespace atk4\core\tests;

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

        // parameter as object
        $class = $m->normalizeClassName(new FactoryMock());
        $this->assertEquals(true, is_object($class));

        // parameter as simple string
        $class = $m->normalizeClassName('MyClass');
        $this->assertEquals('MyClass', $class);

        // parameter as namespaced string
        $class = $m->normalizeClassName('a\b\MyClass');
        $this->assertEquals('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b\MyClass');
        $this->assertEquals('a\b\MyClass', $class);

        $class = $m->normalizeClassName('a/b/MyClass');
        $this->assertEquals('a\b\MyClass', $class);

        // with prefix
        $class = $m->normalizeClassName('MyClass', 'model');
        $this->assertEquals('Model_MyClass', $class);

        $class = $m->normalizeClassName('a\b\MyClass', 'model');
        $this->assertEquals('a\b\Model_MyClass', $class);

        $class = $m->normalizeClassName('a\b\My_Class', 'model');
        $this->assertEquals('a\b\Model_My_Class', $class);

        $class = $m->normalizeClassName('a\b\Model_MyClass', 'model');
        $this->assertEquals('a\b\Model_MyClass', $class);

        $class = $m->normalizeClassName('a\b\model_MyClass', 'model');
        $this->assertEquals('a\b\Model_model_MyClass', $class);
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
        $m = new FactoryMock();

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
    }
}

// @codingStandardsIgnoreStart
class FactoryMock
{
    use FactoryTrait;
}
// @codingStandardsIgnoreEnd
