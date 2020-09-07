<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\Exception;
use atk4\core\HookBreaker;
use atk4\core\HookTrait;

/**
 * @coversDefaultClass \atk4\core\HookTrait
 */
class HookTraitTest extends AtkPhpunit\TestCase
{
    public function testArguments()
    {
        $m = new HookMock();

        $result = 0;
        $m->onHook('test1', function () use (&$result) {
            ++$result;
        }, [0]);

        $this->assertSame(0, $result);

        $m->onHook('test1', function () use (&$result) {
            ++$result;
        }, [5]);

        $this->assertSame(0, $result);
    }

    /**
     * Test constructor.
     */
    public function testBasic()
    {
        $m = new HookMock();
        $result = 0;

        $m->onHook('test1', function () use (&$result) {
            ++$result;
        });

        $this->assertSame(0, $result);

        $m->hook('test1');
        $m->hook('test1');
        $this->assertSame(2, $result);
    }

    public function testAdvanced()
    {
        $m = new HookMock();
        $result = 20;

        $m->onHook('test1', function () use (&$result) {
            ++$result;
        });

        $m->onHook('test1', function () use (&$result) {
            $result = 0;
        }, [], 1);

        $m->hook('test1'); // zero will be executed first, then increment
        $this->assertSame(1, $result);
    }

    private $result = 0;

    public function tst($obj = null, $inc = 1)
    {
        if ($obj === null) {
            // because phpunit tries to execute this method
            return;
        }
        $this->result += $inc;
    }

    public function testHookException1()
    {
        // wrong 2nd argument
        $m = new HookMock();

        $result = '';
        $m->onHook('tst', function ($m, $arg) use (&$result) {
            $result .= $arg;
        });

        $m->hook('tst', ['parameter']);

        $this->assertSame('parameter', $result);
    }

    public function testOrder()
    {
        $m = new HookMock();
        $ind = $m->onHook('spot', function () {
            return 3;
        }, [], -1);
        $m->onHook('spot', function () {
            return 2;
        }, [], -5);
        $m->onHook('spot', function () {
            return 1;
        }, [], -5);

        $m->onHook('spot', function () {
            return 4;
        }, [], 0);
        $m->onHook('spot', function () {
            return 5;
        }, [], 0);

        $m->onHook('spot', function () {
            return 10;
        }, [], 1000);

        $m->onHook('spot', function () {
            return 6;
        }, [], 2);
        $m->onHook('spot', function () {
            return 7;
        }, [], 5);
        $m->onHook('spot', function () {
            return 8;
        });
        $m->onHook('spot', function () {
            return 9;
        }, [], 5);

        $ret = $m->hook('spot');

        $this->assertSame([
            $ind + 2 => 1,
            $ind + 1 => 2,
            $ind => 3,
            $ind + 3 => 4,
            $ind + 4 => 5,
            $ind + 6 => 6,
            $ind + 7 => 7,
            $ind + 8 => 8,
            $ind + 9 => 9,
            $ind + 5 => 10,
        ], $ret);
    }

    public function testMulti()
    {
        $obj = new HookMock();

        $mulFunc = function ($obj, $a, $b) {
            return $a * $b;
        };
        $addFunc = function ($obj, $a, $b) {
            return $a + $b;
        };

        $obj->onHook('test', $mulFunc);
        $obj->onHook('test', $addFunc);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertSame([4, 4], $res1);

        $res2 = $obj->hook('test', [3, 3]);
        $this->assertSame([9, 6], $res2);
    }

    public function testArgs()
    {
        $obj = new HookMock();

        $mulFunc = function ($obj, $a, $b) {
            return $a * $b;
        };
        $addFunc = function ($obj, $a, $b) {
            return $a + $b;
        };
        $powFunc = function ($obj, $a, $b, $power) {
            return $a ** $power + $b ** $power;
        };

        $obj->onHook('test', $mulFunc);
        $obj->onHook('test', $addFunc);
        $obj->onHook('test', $powFunc, [2]);
        $obj->onHook('test', $powFunc, [7]);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertSame([4, 4, 8, 256], $res1);

        $res2 = $obj->hook('test', [2, 3]);
        $this->assertSame([6, 5, 13, 2315], $res2);
    }

    public function testReferences()
    {
        $obj = new HookMock();

        $incFunc = function ($obj, &$a) {
            ++$a;
        };

        $obj->onHook('inc', $incFunc);
        $v = 1;
        $a = [&$v];
        $obj->hook('inc', $a);

        $this->assertSame([2], $a);

        $obj = new HookMock();

        $v = 1;
        $obj->onHook('inc', $incFunc);
        $obj->hook('inc', [&$v]);

        $this->assertSame(2, $v);
    }

    public function testBreakHook()
    {
        $m = new HookMock();
        $m->result = 0;

        $incFunc = function ($obj) {
            ++$obj->result;
            if ($obj->result === 2) {
                $obj->breakHook('stop');
            }
        };

        $m->onHook('inc', $incFunc);
        $m->onHook('inc', $incFunc);
        $m->onHook('inc', $incFunc);

        $ret = $m->hook('inc');
        $this->assertSame(2, $m->result);
        $this->assertSame('stop', $ret);
    }

    public function testBreakHookBrokenBy()
    {
        $m = new HookMock();

        $m->onHook('inc', function () use ($m) {
            $m->breakHook('stop');
        });

        /** @var HookBreaker $brokenBy */
        $ret = $m->hook('inc', [], $brokenBy);
        $this->assertSame('stop', $ret);
        $this->assertInstanceOf(HookBreaker::class, $brokenBy);
        $this->assertSame('stop', $brokenBy->getReturnValue());
    }

    public function testExceptionInHook()
    {
        $this->expectException(Exception::class);
        $m = new HookMock();
        $m->result = 0;

        $m->onHook('inc', function ($obj) {
            throw new \atk4\core\Exception('stuff went wrong');
        });
        $ret = $m->hook('inc');
    }

    public function testOnHookShort()
    {
        $m = new HookMock();

        // unbound callback
        $self = $this;
        $m->onHookShort('inc', static function (...$args) use ($self) {
            $self->assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);

        // bound callback
        $m->onHookShort('inc', function (...$args) {
            $this->assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);
    }

    public function testOnHookInvokeMethod()
    {
        $m = new HookMock();
        $this->assertSame(0, $m->result);
        $m->onHookMethod('inc', 'incMethod');
        $m->hook('inc');
        $this->assertSame(1, $m->result);
        $m->hook('inc');
        $this->assertSame(2, $m->result);

        $m = new class() extends HookMock {
            public function returnArgs(...$args)
            {
                return $args;
            }
        };
        $m->onHookMethod('check_args', 'returnArgs', ['x']);
        $this->assertSame([['y', 'x']], $m->hook('check_args', ['y']));
    }

    public function testCloningSafety()
    {
        $makeMock = function () {
            return new class() extends HookMock {
                public function makeCallback(): \Closure
                {
                    return function () {
                        return $this;
                    };
                }
            };
        };

        // unbound callback
        $m = $makeMock();
        $m->onHook('inc', static function () {});
        $m->onHookShort('inc', static function () {});
        $m->onHookShort('null_scope_class', \Closure::fromCallable('trim'), ['x']);
        $m = clone $m;
        foreach ($m->hook('inc') as $hookRes) {
            $this->assertNull($hookRes);
        }

        // callback bound to the same object
        $m = $makeMock();
        $m->onHook('inc', $m->makeCallback());
        $m->onHookShort('inc', $m->makeCallback());
        $m->onHookMethod('inc', 'incMethod');
        $m = clone $m;
        foreach ($m->hook('inc') as $hookRes) {
            $this->assertSame($m, $hookRes);
        }
        $m = clone $m; // clone twice
        foreach ($m->hook('inc') as $hookRes) {
            $this->assertSame($m, $hookRes);
        }
        $this->assertSame(2, $m->result);

        // callback bound to a different object
        $m = $makeMock();
        $m->onHook('inc', (clone $m)->makeCallback());
        $m = clone $m;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object can not be cloned with hook bound to a different object than this');
        $m->hook('inc');
    }
}

// @codingStandardsIgnoreStart
class HookMock
{
    use HookTrait;

    public $result = 0;

    public function incMethod()
    {
        ++$this->result;

        return $this;
    }
}

class HookWithDynamicMethodMock extends HookMock
{
    use \atk4\core\DynamicMethodTrait;

    public function foo()
    {
    }
}
// @codingStandardsIgnoreEnd
