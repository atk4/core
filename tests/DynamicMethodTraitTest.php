<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DynamicMethodTrait;
use Atk4\Core\Exception;
use Atk4\Core\HookTrait;
use Atk4\Core\Phpunit\TestCase;

class DynamicMethodTraitTest extends TestCase
{
    protected function createSumFx(): \Closure
    {
        return static function ($m, $a, $b) {
            return $a + $b;
        };
    }

    public function testConstruct(): void
    {
        $m = new DynamicMethodMock();
        $m->addMethod('test', static function () {
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

        \Closure::bind(static function () use ($m) {
            $m->protectedMethod();
        }, null, DynamicMethodMock::class)();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to protected method ' . DynamicMethodMock::class
            . '::protectedMethod() from global scope');
        \Closure::bind(static function () use ($m) {
            $m->protectedMethod(); // @phpstan-ignore method.protected
        }, null, null)();
    }

    public function testExceptionUndefinedWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ' . DynamicMethodWithoutHookMock::class . '::unknownMethod()');
        $m->unknownMethod();
    }

    public function testExceptionAddWithoutHookTrait(): void
    {
        $m = new DynamicMethodWithoutHookMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object must use HookTrait for dynamic method support');
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

    public function testDoubleMethodException(): void
    {
        $m = new DynamicMethodMock();
        $m->addMethod('sum', $this->createSumFx());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Method is already defined');
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
}

class DynamicMethodMock
{
    use DynamicMethodTrait;
    use HookTrait;

    private function privateMethod(): void {}

    protected function protectedMethod(): void
    {
        $this->privateMethod();
    }
}
class DynamicMethodWithoutHookMock
{
    use DynamicMethodTrait;
}
