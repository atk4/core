<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\DynamicMethodTrait;
use Atk4\Core\Exception;
use Atk4\Core\HookTrait;
use Atk4\Core\Phpunit\TestCase;

class DynamicMethodTraitTest extends TestCase
{
    public function createSumFx(bool $forGlobal = false): \Closure
    {
        // fix broken indentation once https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/6463 is fixed
        return $forGlobal
            ? function ($m, $obj, $a, $b) {
                return $a + $b;
            }
        : function ($m, $a, $b) {
            return $a + $b;
        };
    }

    public function testConstruct(): void
    {
        $m = new DynamicMethodMock();
        $m->addMethod('test', function () {
            return 'world';
        });

        self::assertTrue($m->hasMethod('test'));

        $res = 'Hello, ' . $m->test();
        self::assertSame('Hello, world', $res);
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
        $this->expectExceptionMessage('Call to private method ' . DynamicMethodMock::class
            . '::privateMethod() from scope ' . static::class);
        $m->__call('privateMethod', []);
    }

    public function testExceptionProtectedMethod(): void
    {
        $m = new DynamicMethodMock();

        \Closure::bind(function () use ($m) {
            $m->protectedMethod();
        }, null, DynamicMethodMock::class)();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to protected method ' . DynamicMethodMock::class
            . '::protectedMethod() from global scope');
        \Closure::bind(function () use ($m) {
            $m->protectedMethod(); // @phpstan-ignore-line
        }, null, null)();
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
        $m->addMethod('sum', $this->createSumFx());
    }

    public function testArguments(): void
    {
        // simple method
        $m = new DynamicMethodMock();
        $m->addMethod('sum', $this->createSumFx());
        $res = $m->sum(3, 5);
        self::assertSame(8, $res);

        $m = new DynamicMethodMock();
        $m->addMethod('getElementCount', \Closure::fromCallable([new ContainerMock(), 'getElementCount']));
        self::assertSame(0, $m->getElementCount());
    }

    /**
     * Can add, check and remove methods.
     */
    public function testWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();
        self::assertFalse($m->hasMethod('sum'));

        self::assertSame($m, $m->removeMethod('sum'));
    }

    public function testDoubleMethodException(): void
    {
        $m = new DynamicMethodMock();
        $m->addMethod('sum', $this->createSumFx());

        $this->expectException(Exception::class);
        $m->addMethod('sum', $this->createSumFx());
    }

    public function testRemoveMethod(): void
    {
        // simple method
        $m = new DynamicMethodMock();
        $m->addMethod('sum', $this->createSumFx());
        self::assertTrue($m->hasMethod('sum'));
        $m->removeMethod('sum');
        self::assertFalse($m->hasMethod('sum'));
    }

    public function testGlobalMethodException1(): void
    {
        // can't add global method without AppScopeTrait and HookTrait
        $m = new DynamicMethodMock();

        $this->expectException(Exception::class);
        $m->addGlobalMethod('sum', $this->createSumFx(true));
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

        $m->addGlobalMethod('sum', $this->createSumFx(true));
        self::assertTrue($m->hasGlobalMethod('sum'));

        $res = $m2->sum(3, 5);
        self::assertSame(8, $res);

        $m->removeGlobalMethod('sum');
        self::assertFalse($m2->hasGlobalMethod('sum'));
    }

    public function testDoubleGlobalMethodException(): void
    {
        $m = new GlobalMethodObjectMock();
        $m->setApp(new GlobalMethodAppMock());

        $m->addGlobalMethod('sum', $this->createSumFx(true));

        $this->expectException(Exception::class);
        $m->addGlobalMethod('sum', $this->createSumFx(true));
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
