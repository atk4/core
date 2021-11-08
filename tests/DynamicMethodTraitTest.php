<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\DynamicMethodTrait;
use Atk4\Core\Exception;
use Atk4\Core\HookTrait;
use Atk4\Core\Phpunit\TestCase;

/**
 * @coversDefaultClass \Atk4\Core\DynamicMethodTrait
 */
class DynamicMethodTraitTest extends TestCase
{
    public function testConstruct(): void
    {
        $m = new DynamicMethodMock();
        $m->addMethod('test', function () {
            return 'world';
        });

        $this->assertTrue($m->hasMethod('test'));

        $res = 'Hello, ' . $m->test();
        $this->assertSame('Hello, world', $res);
    }

    public function testExceptionUndefinedMethod(): void
    {
        $m = new DynamicMethodMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ' . DynamicMethodMock::class . '::unknownMethod()');
        $m->unknownMethod();
    }

    public function testExceptionPrivateMethod(): void
    {
        $m = new DynamicMethodMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to private method ' . DynamicMethodMock::class . '::privateMethod()');
        $m->privateMethod();
    }

    public function testExceptionProtectedMethod(): void
    {
        $m = new DynamicMethodMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to protected method ' . DynamicMethodMock::class . '::protectedMethod()');
        $m->protectedMethod();
    }

    public function testExceptionUndefinedWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();

        $this->expectException(\Error::class);
        $m->unknownMethod();
    }

    public function testExceptionUndefinedWithoutHookTrait2(): void
    {
        $m = new GlobalMethodObjectMock();
        $m->setApp(new GlobalMethodAppMock());

        $this->expectException(\Error::class);
        $m->unknownMethod();
    }

    public function testExceptionAddWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();

        $this->expectException(Exception::class);
        $m->addMethod('sum', function ($m, $a, $b) {
            return $a + $b;
        });
    }

    /**
     * Test arguments.
     */
    public function testArguments(): void
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
    public function testWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();
        $this->assertFalse($m->hasMethod('sum'));

        $this->assertSame($m, $m->removeMethod('sum'));
    }

    public function testDoubleMethodException(): void
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
    public function testRemoveMethod(): void
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

    public function testGlobalMethodException1(): void
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
    public function testGlobalMethods(): void
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

    public function testDoubleGlobalMethodException(): void
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

class DynamicMethodMock
{
    use DynamicMethodTrait;
    use HookTrait;

    private function privateMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
        $this->privateMethod();
    }
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
