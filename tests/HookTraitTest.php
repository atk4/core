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

        $m->onHook('test1', static function () use (&$result) {
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

        $m->onHook('test1', static function () use (&$result) {
            ++$result;
        });

        $m->onHook('test1', static function () use (&$result) {
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
        $m->onHook('tst', static function ($m, $arg) use (&$result) {
            $result .= $arg;
        });

        $m->hook('tst', ['parameter']);

        self::assertSame('parameter', $result);
    }

    public function testOrder(): void
    {
        $m = new HookMock();

        $m->onHook('spot', static function () {
            return 3;
        }, [], -1);
        $m->onHook('spot', static function () {
            return 2;
        }, [], -5);
        $m->onHook('spot', static function () {
            return 1;
        }, [], -5);

        $m->onHook('spot', static function () {
            return 4;
        }, [], 0);
        $m->onHook('spot', static function () {
            return 5;
        }, [], 0);

        $m->onHook('spot', static function () {
            return 10;
        }, [], 1000);

        $m->onHook('spot', static function () {
            return 6;
        }, [], 2);
        $m->onHook('spot', static function () {
            return 7;
        }, [], 5);
        $m->onHook('spot', static function () {
            return 8;
        });
        $m->onHook('spot', static function () {
            return 9;
        }, [], 5);

        $ret = $m->hook('spot');

        self::assertSame([
            2 => 1,
            1 => 2,
            0 => 3,
            3 => 4,
            4 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            5 => 10,
        ], $ret);
    }

    public function testMulti(): void
    {
        $obj = new HookMock();

        $mulFx = static function ($obj, $a, $b) {
            return $a * $b;
        };
        $addFx = static function ($obj, $a, $b) {
            return $a + $b;
        };

        $obj->onHook('test', $mulFx);
        $obj->onHook('test', $addFx);

        $res1 = $obj->hook('test', [2, 2]);
        self::assertSame([4, 4], $res1);

        $res2 = $obj->hook('test', [3, 3]);
        self::assertSame([9, 6], $res2);
    }

    public function testUpdateWhenActive(): void
    {
        $m = new HookMock();

        $addHooksFx = static function (int $priority, string $res) use ($m) {
            $m->onHook('spot', static function () use ($m, $priority, $res) {
                $m->onHook('spot', static function () use ($res) {
                    return $res . 'a';
                }, [], $priority);
                $m->onHook('spot', static function () use ($m, $priority, $res) {
                    $m->removeHook('spot', $priority);

                    return $res . 'b';
                }, [], $priority);

                return $res;
            }, [], $priority);
        };

        $addHooksFx(-2, '1');
        $addHooksFx(-1, '2');
        $addHooksFx(-1, '3');
        $addHooksFx(0, '4');
        $addHooksFx(1, '5');
        $addHooksFx(1, '6');

        $priority = 10;
        $indexPrevious = $m->onHook('spot', static fn () => 'x', [], $priority);
        $indexCurrent = $m->onHook('spot', static function () use ($m, $priority, $indexPrevious, &$indexCurrent, &$indexNext) {
            $m->onHook('spot', static fn () => 'ya', [], $priority);

            $m->removeHook('spot', $indexPrevious, true);
            $m->removeHook('spot', $indexCurrent, true);
            $m->removeHook('spot', $indexNext, true); // @phpstan-ignore argument.type

            $m->onHook('spot', static function () use ($m, $priority) {
                $m->removeHook('spot', $priority);

                return 'yb';
            }, [], $priority);

            return 'y';
        }, [], $priority);
        $indexNext = $m->onHook('spot', static fn () => 'z', [], $priority);

        $ret = $m->hook('spot');

        self::assertSame([
            0 => '1',
            10 => '1b',
            2 => '3',
            12 => '3b',
            3 => '4',
            13 => '4a',
            14 => '4b',
            4 => '5',
            5 => '6',
            15 => '5a',
            16 => '5b',
            6 => 'x',
            7 => 'y',
            19 => 'ya',
            20 => 'yb',
        ], $ret);
    }

    public function testArgs(): void
    {
        $obj = new HookMock();

        $mulFx = static function ($obj, $a, $b) {
            return $a * $b;
        };
        $addFx = static function ($obj, $a, $b) {
            return $a + $b;
        };
        $powFx = static function ($obj, $a, $b, $power) {
            return $a ** $power + $b ** $power;
        };

        $obj->onHook('test', $mulFx);
        $obj->onHook('test', $addFx);
        $obj->onHook('test', $powFx, [2]);
        $obj->onHook('test', $powFx, [7]);

        $res1 = $obj->hook('test', [2, 2]);
        self::assertSame([4, 4, 8, 256], $res1);

        $res2 = $obj->hook('test', [2, 3]);
        self::assertSame([6, 5, 13, 2315], $res2);
    }

    public function testReferences(): void
    {
        $obj = new HookMock();

        $incFx = static function ($obj, int &$value) {
            ++$value;
        };

        $obj->onHook('inc', $incFx);
        $v = 1;
        $a = [&$v];
        $obj->hook('inc', $a);

        self::assertSame([2], $a);

        $obj = new HookMock();

        $v = 1;
        $obj->onHook('inc', $incFx);
        $obj->hook('inc', [&$v]);

        self::assertSame(2, $v);
    }

    public function testBreakHook(): void
    {
        $m = new HookMock();
        $m->result = 0;

        $incFx = static function ($obj) {
            ++$obj->result;
            if ($obj->result === 2) {
                $obj->breakHook('stop');
            }
        };

        $m->onHook('inc', $incFx);
        $m->onHook('inc', $incFx);
        $m->onHook('inc', $incFx);

        $ret = $m->hook('inc');
        self::assertSame(2, $m->result);
        self::assertSame('stop', $ret);
    }

    public function testBreakHookBrokenBy(): void
    {
        $m = new HookMock();

        $m->onHook('inc', static function () use ($m) {
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

        $m->onHook('inc', static function ($obj) {
            throw new Exception('stuff went wrong');
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('stuff went wrong');
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
            self::assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);

        // bound callback
        $m->onHookShort('inc', static function (...$args) {
            self::assertSame(['y', 'x'], $args);
        }, ['x']);
        $m->hook('inc', ['y']);
    }

    public function testCloningSafetyUnboundClosure(): void
    {
        $m = new HookMock();
        $m->onHook('inc', static function () {});
        $m->onHookShort('inc', static function () {});
        $m->onHookShort('null_scope_class', \Closure::fromCallable('trim'), ['x']);
        $m = clone $m;
        foreach ($m->hook('inc') as $v) {
            self::assertNull($v);
        }
    }

    public function testCloningSafetyBoundClosureSameObject(): void
    {
        $m = new HookMock();
        $m->onHook('inc', $m->makeIncrementResultFx());
        $m->onHookShort('inc', $m->makeIncrementResultFx());
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
    }

    public function testCloningSafetyBoundClosureDifferentObjectException(): void
    {
        $m = new HookMock();
        $m->onHook('inc', (clone $m)->makeIncrementResultFx());
        $m = clone $m;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object cannot be cloned with hook bound to a different object than this');
        $m->hook('inc');
    }

    public function testOnHookDynamic(): void
    {
        $m = new HookMock();

        $hookThis = $m;
        $m->onHookDynamic('inc', static function () use (&$hookThis) {
            return $hookThis;
        }, $m->makeIncrementResultFx());

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
            self::isPhpunit9x() ? $this->getName(false) : $this->name(); // prevent PHP CS Fixer to make this anonymous function static

            return $m;
        }, $m->makeIncrementResultFx());
    }

    public function testOnHookDynamicGetterNullException(): void
    {
        $m = new HookMock();

        $m->onHookDynamic('inc', static function (HookMock $m) { // @phpstan-ignore argument.type
            return null;
        }, $m->makeIncrementResultFx());

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('New $this must be an object');
        $m->hook('inc');
    }

    public function testPassByReference(): void
    {
        $value = 0;
        $m = new HookMock();
        $m->onHook('inc', static function ($ignoreObject, $ignore1st, int &$value) {
            ++$value;
        });
        $m->hook('inc', ['x', &$value]);
        self::assertSame(1, $value);
        $m->hook('inc', ['x', &$value]);
        self::assertSame(2, $value);

        $value = 0;
        $m = new HookMock();
        $m->onHookShort('inc', function ($ignore1st, int &$value) {
            self::isPhpunit9x() ? $this->getName(false) : $this->name(); // prevent PHP CS Fixer to make this anonymous function static

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
            $this->makeIncrementResultFx(); // @phpstan-ignore method.notFound (prevent PHP CS Fixer to make this anonymous function static)

            ++$value;
        });
        $m->hook('inc', ['x', &$value]);
        self::assertSame(1, $value);
        $m->hook('inc', ['x', &$value]);
        self::assertSame(2, $value);
    }

    public function testHasCallbacks(): void
    {
        $m = new HookMock();
        $index = $m->onHook('foo', static function () {});

        self::assertTrue($m->hookHasCallbacks('foo'));
        self::assertFalse($m->hookHasCallbacks('bar'));

        self::assertTrue($m->hookHasCallbacks('foo', 5));
        self::assertFalse($m->hookHasCallbacks('foo', 10));
        self::assertFalse($m->hookHasCallbacks('bar', 5));

        self::assertTrue($m->hookHasCallbacks('foo', $index, true));
        self::assertFalse($m->hookHasCallbacks('foo', $index + 1, true));
        self::assertFalse($m->hookHasCallbacks('foo', $index - 1, true));
        self::assertFalse($m->hookHasCallbacks('bar', $index, true));
    }

    public function testRemove(): void
    {
        $m = new HookMock();
        $indexA = $m->onHook('foo', static function () {}, [], 2);
        $indexB = $m->onHook('foo', static function () {});
        $indexC = $m->onHook('foo', static function () {});

        self::assertTrue($m->hookHasCallbacks('foo', $indexA, true));
        self::assertTrue($m->hookHasCallbacks('foo', $indexB, true));
        self::assertTrue($m->hookHasCallbacks('foo', $indexC, true));

        $m->removeHook('foo', $indexC, true);
        self::assertTrue($m->hookHasCallbacks('foo', $indexA, true));
        self::assertTrue($m->hookHasCallbacks('foo', $indexB, true));
        self::assertFalse($m->hookHasCallbacks('foo', $indexC, true));

        $m->removeHook('foo', 2);
        self::assertFalse($m->hookHasCallbacks('foo', $indexA, true));
        self::assertTrue($m->hookHasCallbacks('foo', $indexB, true));
        self::assertFalse($m->hookHasCallbacks('foo', $indexC, true));

        self::assertTrue($m->hookHasCallbacks('foo'));
        $m->removeHook('foo');
        self::assertFalse($m->hookHasCallbacks('foo'));
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

    public function makeIncrementResultFx(): \Closure
    {
        return function () {
            $this->incrementResult();

            return $this;
        };
    }
}

class HookWithDynamicMethodMock extends HookMock
{
    use DynamicMethodTrait;

    public function foo(): void {}
}
