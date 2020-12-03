<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\AtkPhpunit;
use Atk4\Core\DynamicMethodTrait;
use Atk4\Core\Exception;
use Atk4\Core\HookTrait;

/**
 * @coversDefaultClass \Atk4\Core\DynamicMethodTrait
 */
class DynamicMethodTraitTest extends AtkPhpunit\TestCase
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

        $this->assertTrue($m->hasMethod('test'));

        $res = 'Hello, ' . $m->test();
        $this->assertSame('Hello, world', $res);
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
        $m->setApp(new GlobalMethodAppMock());
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
        $this->assertSame(8, $res);

        $m = new DynamicMethodMock();
        $m->addMethod('getElementCount', \Closure::fromCallable([new ContainerMock(), 'getElementCount']));
        $this->assertSame(0, $m->getElementCount());
    }

    /**
     * Can add, check and remove methods.
     */
    public function testWithoutHookTrait()
    {
        $m = new DynamicMethodWithoutHookMock();
        $this->assertFalse($m->hasMethod('sum'));

        $this->assertSame($m, $m->removeMethod('sum'));
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
        $m->setApp($app);

        $m2 = new GlobalMethodObjectMock();
        $m2->setApp($app);

        $m->addGlobalMethod('sum', function ($m, $obj, $a, $b) {
            return $a + $b;
        });
        $this->assertTrue($m->hasGlobalMethod('sum'));

        $res = $m2->sum(3, 5);
        $this->assertSame(8, $res);

        $m->removeGlobalMethod('sum');
        $this->assertFalse($m2->hasGlobalMethod('sum'));
    }

    public function testDoubleGlobalMethodException()
    {
        $this->expectException(Exception::class);

        $m = new GlobalMethodObjectMock();
        $m->setApp(new GlobalMethodAppMock());

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
