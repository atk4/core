<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DynamicMethodTrait;
use Atk4\Core\Exception;
use Atk4\Core\HookBreaker;
use Atk4\Core\HookTrait;
use Atk4\Core\Phpunit\TestCase;

class HookTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new HookMock();
        $result = 0;

        $m->onHook('test1', function () use (&$result) {
            ++$result;
        });

        self::assertSame(0, $result);

        $m->hook('test1');
        $m->hook('test1');
        self::assertSame(2, $result);
    }

    public function testAdvanced(): void
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
        self::assertSame(1, $result);
    }

    public function testHookException1(): void
    {
        // wrong 2nd argument
        $m = new HookMock();

        $result = '';
        $m->onHook('tst', function ($m, $arg) use (&$result) {
            $result .= $arg;
        });

        $m->hook('tst', ['parameter']);

        self::assertSame('parameter', $result);
    }

    public function testOrder(): void
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

        self::assertSame([
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

    public function testMulti(): void
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
        self::assertSame([4, 4], $res1);

        $res2 = $obj->hook('test', [3, 3]);
        self::assertSame([9, 6], $res2);
    }

    public function testArgs(): void
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
        self::assertSame([4, 4, 8, 256], $res1);

        $res2 = $obj->hook('test', [2, 3]);
        self::assertSame([6, 5, 13, 2315], $res2);
    }

    public function testReferences(): void
    {
        $obj = new HookMock();

        $incFunc = function ($obj, &$a) {
            ++$a;
        };

        $obj->onHook('inc', $incFunc);
        $v = 1;
        $a = [&$v];
        $obj->hook('inc', $a);

        self::assertSame([2], $a);

        $obj = new HookMock();

        $v = 1;
        $obj->onHook('inc', $incFunc);
        $obj->hook('inc', [&$v]);

        self::assertSame(2, $v);
    }

    public function testBreakHook(): void
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
        self::assertSame(2, $m->result);
        self::assertSame('stop', $ret);
    }

    public function testBreakHookBrokenBy(): void
    {
        $m = new HookMock();

        $m->onHook('inc', function () use ($m) {
            $m->breakHook('stop');
        });

        $ret = $m->hook('inc', [], $brokenBy);
        self::assertSame('stop', $ret);
        self::assertInstanceOf(HookBreaker::class, $brokenBy);
        self::assertSame('stop', $brokenBy->getReturnValue());
    }

    public function testExceptionInHook(): void
    {
        $m = new HookMock();
        $m->result = 0;

        $m->onHook('inc', function ($obj) {
            throw new Exception('stuff went wrong');
        });

        $this->expectException(Exception::class);
        $m->hook('inc');
    }

    public function testOnHookShort(): void
    {
        $m = new HookMock();

        // unscoped callback
        $m->onHookShort('inc', \Closure::bind(static function (...$args) {
            TestCase::assertSame(['y', 'x'], $args);
        }, null, null), ['x']);
        $m->hook('inc', ['y']);

        // unbound callback
        $m->onHookShort('inc', static function (...$args) {
            static::assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);

        // bound callback
        $m->onHookShort('inc', function (...$args) {
            static::assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);
    }

    public function testCloningSafety(): void
    {
        $makeMock = function () {
            return new class() extends HookMock {
                public function makeCallback(): \Closure
                {
                    return function () {
                        $this->incrementResult();

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
        foreach ($m->hook('inc') as $v) {
            self::assertNull($v);
        }

        // callback bound to the same object
        $m = $makeMock();
        $m->onHook('inc', $m->makeCallback());
        $m->onHookShort('inc', $m->makeCallback());
        $m = clone $m;
        foreach ($m->hook('inc') as $v) {
            self::assertSame($m, $v);
        }
        self::assertSame(2, $m->result);
        foreach ($m->hook('inc') as $v) { // 2nd dispatch
            self::assertSame($m, $v);
        }
        self::assertSame(4, $m->result);
        $m = clone $m; // 2nd clone
        foreach ($m->hook('inc') as $v) {
            self::assertSame($m, $v);
        }
        self::assertSame(6, $m->result);

        // callback bound to a different object
        $m = $makeMock();
        $m->onHook('inc', (clone $m)->makeCallback());
        $m = clone $m;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object cannot be cloned with hook bound to a different object than this');
        $m->hook('inc');
    }

    public function testOnHookDynamic(): void
    {
        $m = new class() extends HookMock {
            public function makeCallback(): \Closure
            {
                return function () {
                    $this->incrementResult();

                    return $this;
                };
            }
        };

        $hookThis = $m;
        $m->onHookDynamic('inc', static function () use (&$hookThis) {
            return $hookThis;
        }, $m->makeCallback());

        self::assertSame([$hookThis], $m->hook('inc'));
        self::assertSame(1, $m->result);

        $mCloned = clone $m;
        self::assertSame([$hookThis], $m->hook('inc'));
        self::assertSame(2, $m->result);
        self::assertSame(1, $mCloned->result);
        self::assertSame([$hookThis], $mCloned->hook('inc'));
        self::assertSame(3, $m->result);
        self::assertSame(1, $mCloned->result);

        $hookThis = $mCloned;
        self::assertSame([$hookThis], $m->hook('inc'));
        self::assertSame(3, $m->result);
        self::assertSame(2, $mCloned->result);
        self::assertSame([$hookThis], $mCloned->hook('inc'));
        self::assertSame(3, $m->result);
        self::assertSame(3, $mCloned->result);

        $hookThis = $m;
        self::assertSame([$hookThis], $m->hook('inc'));
        self::assertSame(4, $m->result);
        self::assertSame(3, $mCloned->result);
        self::assertSame([$hookThis], $mCloned->hook('inc'));
        self::assertSame(5, $m->result);
        self::assertSame(3, $mCloned->result);

        $m->onHookDynamicShort('incShort', static function () use (&$hookThis) {
            return $hookThis;
        }, function (...$args) use (&$hookThis) {
            TestCase::assertSame($hookThis, $this);
            TestCase::assertSame(['y', 'x'], $args);

            return $this->hook('inc');
        }, ['x']);
        self::assertSame([1 => [$hookThis]], $m->hook('incShort', ['y']));
        self::assertSame(6, $m->result);
        self::assertSame(3, $mCloned->result);

        $mCloned = clone $m;
        self::assertSame([1 => [$hookThis]], $m->hook('incShort', ['y']));
        self::assertSame(7, $m->result);
        self::assertSame(6, $mCloned->result);
        self::assertSame([1 => [$hookThis]], $mCloned->hook('incShort', ['y']));
        self::assertSame(8, $m->result);
        self::assertSame(6, $mCloned->result);

        $hookThis = $mCloned;
        self::assertSame([1 => [$hookThis]], $m->hook('incShort', ['y']));
        self::assertSame(8, $m->result);
        self::assertSame(7, $mCloned->result);
        self::assertSame([1 => [$hookThis]], $mCloned->hook('incShort', ['y']));
        self::assertSame(8, $m->result);
        self::assertSame(8, $mCloned->result);

        $hookThis = $m;
        self::assertSame([1 => [$hookThis]], $m->hook('incShort', ['y']));
        self::assertSame(9, $m->result);
        self::assertSame(8, $mCloned->result);
        self::assertSame([1 => [$hookThis]], $mCloned->hook('incShort', ['y']));
        self::assertSame(10, $m->result);
        self::assertSame(8, $mCloned->result);
    }

    public function testOnHookDynamicBoundGetterException(): void
    {
        $m = new HookMock();

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('New $this getter must be static');
        $m->onHookDynamic('inc', function (HookMock $m) {
            return $m;
        }, fn ($v) => $v);
    }

    public function testOnHookDynamicGetterNullException(): void
    {
        $m = new HookMock();

        $m->onHookDynamic('inc', static function (HookMock $m) { // @phpstan-ignore-line
            return null;
        }, fn ($v) => $v);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('New $this must be an object');
        $m->hook('inc');
    }

    public function testPassByReference(): void
    {
        $value = 0;
        $m = new HookMock();
        $m->onHook('inc', function ($ignoreObject, $ignore1st, int &$value) {
            ++$value;
        });
        $m->hook('inc', ['x', &$value]);
        self::assertSame(1, $value);
        $m->hook('inc', ['x', &$value]);
        self::assertSame(2, $value);

        $value = 0;
        $m = new HookMock();
        $m->onHookShort('inc', function ($ignore1st, int &$value) {
            ++$value;
        });
        $m->hook('inc', ['x', &$value]);
        self::assertSame(1, $value);
        $m->hook('inc', ['x', &$value]);
        self::assertSame(2, $value);

        $value = 0;
        $m = new HookMock();
        $m->onHookDynamic('inc', static function () use ($m) {
            return clone $m;
        }, function ($ignoreObject, $ignore1st, int &$value) {
            ++$value;
        });
        $m->hook('inc', ['x', &$value]);
        self::assertSame(1, $value);
        $m->hook('inc', ['x', &$value]);
        self::assertSame(2, $value);
    }
}

class HookMock
{
    use HookTrait;

    /** @var int */
    public $result = 0;

    public function incrementResult(): void
    {
        ++$this->result;
    }
}

class HookWithDynamicMethodMock extends HookMock
{
    use DynamicMethodTrait;

    public function foo(): void
    {
    }
}
