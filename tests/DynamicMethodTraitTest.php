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

    public function testArguments()
    {
        $m = new DynamicMethodMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });

        $res = $m->sum(3, 5);
        $this->assertEquals(8, $res);
    }

    /**
     * @expectedException Exception
     */
    public function testDoubleMethod()
    {
        $m = new DynamicMethodMock();
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
    }

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
    }
}

// @codingStandardsIgnoreStart
class DynamicMethodMock
{
    use HookTrait;
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
