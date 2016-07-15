<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\DynamicMethodTrait;
use atk4\core\HookTrait;

/**
 * @coversDefaultClass \atk4\core\DynamicMethodTrait
 */
class DynamicMethodTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testConstruct()
    {
        $m = new DynamicMethodMock();
        $m->addMethod('test', function () {
            return 'world';
        });

        $this->assertEquals(true, $m->hasMethod('test'));

        $res = 'Hello, '.$m->test();
        $this->assertEquals('Hello, world', $res);
    }

    /**
     * @expectedException     Exception
     */
    public function testException1()
    {
        // can't call undefined method
        $m = new DynamicMethodMock();
        $m->unknownMethod();
    }

    /**
     * @expectedException     Exception
     */
    public function testException2()
    {
        // can't call method without HookTrait or AppScope+Hook traits
        $m = new DynamicMethodWithoutHookMock();
        $m->unknownMethod();
    }

    /**
     * @expectedException     Exception
     */
    public function testException3()
    {
        // can't add method without HookTrait
        $m = new DynamicMethodWithoutHookMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
    }

    /**
     * @expectedException     Exception
     */
    public function testException4()
    {
        // can't call method without HookTrait or AppScope+Hook traits
        $m = new GlobalMethodObjectMock();
        $m->app = new GlobalMethodAppMock();
        $m->unknownMethod();
    }

    /**
     * Test arguments.
     */
    public function testArguments()
    {
        // simple method
        $m = new DynamicMethodMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
        $res = $m->sum(3, 5);
        $this->assertEquals(8, $res);

        // method name as CSV
        $m = new DynamicMethodMock();
        $m->addMethod(['min,less'], function ($m, $a, $b) {
            return min($a, $b);
        });
        $res = $m->min(3, 5);
        $this->assertEquals(3, $res);

        $m = new DynamicMethodMock();
        $m->addMethod(['min, less'], function ($m, $a, $b) {
            return min($a, $b);
        });
        $res = $m->less(5, 3);
        $this->assertEquals(3, $res);

        // method name as array
        $m = new DynamicMethodMock();
        $m->addMethod(['min', 'less'], function ($m, $a, $b) {
            return min($a, $b);
        });
        $res = $m->min(3, 5);
        $this->assertEquals(3, $res);
        $res = $m->less(5, 3);
        $this->assertEquals(3, $res);

        // callable as object
        $m = new DynamicMethodMock();
        $m->addMethod('getElementCount', new ContainerMock());
        $this->assertEquals(0, $m->getElementCount());
    }

    /**
     * Can add, check and remove methods.
     */
    public function testWithoutHookTrait()
    {
        $m = new DynamicMethodWithoutHookMock();
        $this->assertEquals(false, $m->hasMethod('sum'));

        $this->assertEquals($m, $m->removeMethod('sum'));
    }

    /**
     * @expectedException Exception
     */
    public function testDoubleMethodException()
    {
        $m = new DynamicMethodMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
    }

    /**
     * Test removing dynamic method.
     */
    public function testRemoveMethod()
    {
        // simple method
        $m = new DynamicMethodMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
        $m->removeMethod('sum');
    }

    /**
     * @expectedException     Exception
     */
    public function testGlobalMethodException1()
    {
        // can't add global method without AppScopeTrait and HookTrait
        $m = new DynamicMethodMock();
        $m->addGlobalMethod('sum', function ($m, $obj, $a, $b) {
            return $a + $b;
        });
    }

    /**
     * Test adding, checking, removing global method.
     */
    public function testGlobalMethods()
    {
        $app = new GlobalMethodAppMock();

        $m = new GlobalMethodObjectMock();
        $m->app = $app;

        $m2 = new GlobalMethodObjectMock();
        $m2->app = $app;

        $m->addGlobalMethod('sum', function ($m, $obj, $a, $b) {
            return $a + $b;
        });
        $this->assertEquals(true, $m->hasGlobalMethod('sum'));

        $res = $m2->sum(3, 5);
        $this->assertEquals(8, $res);

        $m->removeGlobalMethod('sum');
        $this->assertEquals(false, $m2->hasGlobalMethod('sum'));
    }

    /**
     * @expectedException Exception
     */
    public function testDoubleGlobalMethodException()
    {
        $m = new GlobalMethodObjectMock();
        $m->app = new GlobalMethodAppMock();

        $m->addGlobalMethod('sum', function ($m, $obj, $a, $b) {
            return $a + $b;
        });
        $m->addGlobalMethod('sum', function ($m, $obj, $a, $b) {
            return $a + $b;
        });
    }
}

// @codingStandardsIgnoreStart
class DynamicMethodMock
{
    use HookTrait;
    use DynamicMethodTrait;
}
class DynamicMethodWithoutHookMock
{
    use DynamicMethodTrait;
}

class GlobalMethodObjectMock
{
    use AppScopeTrait;
    use DynamicMethodTrait;
}

class GlobalMethodAppMock
{
    use HookTrait;
}
// @codingStandardsIgnoreEnd
