<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\DynamicMethodTrait;
use atk4\core\Exception;
use atk4\core\HookTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \atk4\core\DynamicMethodTrait
 */
class DynamicMethodTraitTest extends TestCase
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

        $res = 'Hello, ' . $m->test();
        $this->assertEquals('Hello, world', $res);
    }

    public function testException1()
    {
        // can't call undefined method
        $this->expectException(Exception::class);
        $m = new DynamicMethodMock();
        $m->unknownMethod();
    }

    public function testException2()
    {
        // can't call method without HookTrait or AppScope+Hook traits
        $this->expectException(Exception::class);
        $m = new DynamicMethodWithoutHookMock();
        $m->unknownMethod();
    }

    public function testException3()
    {
        // can't add method without HookTrait
        $this->expectException(Exception::class);
        $m = new DynamicMethodWithoutHookMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
    }

    public function testException4()
    {
        // can't call method without HookTrait or AppScope+Hook traits
        $this->expectException(Exception::class);
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

    public function testDoubleMethodException()
    {
        $this->expectException(Exception::class);

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
        $this->assertTrue($m->hasMethod(('sum')));
        $m->removeMethod('sum');
        $this->assertFalse($m->hasMethod(('sum')));
    }

    public function testGlobalMethodException1()
    {
        // can't add global method without AppScopeTrait and HookTrait
        $this->expectException(Exception::class);
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

    public function testDoubleGlobalMethodException()
    {
        $this->expectException(Exception::class);

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
