<?php

namespace atk4\core\tests;

use atk4\core\Exception;
use atk4\core\HookTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \atk4\core\HookTrait
 */
class HookTraitTest extends TestCase
{
    public function testArguments()
    {
        $m = new HookMock();

        $result = 0;
        $m->onHook('test1', function () use (&$result) {
            $result++;
        }, 0);

        $this->assertEquals(0, $result);

        $m->onHook('test1', function () use (&$result) {
            $result++;
        }, 5);

        $this->assertEquals(0, $result);
    }

    /**
     * Test constructor.
     */
    public function testBasic()
    {
        $m = new HookMock();
        $result = 0;

        $m->onHook('test1', function () use (&$result) {
            $result++;
        });

        $this->assertEquals(0, $result);

        $m->hook('test1');
        $m->hook('test1');
        $this->assertEquals(2, $result);
    }

    public function testAdvanced()
    {
        $m = new HookMock();
        $result = 20;

        $m->onHook('test1', function () use (&$result) {
            $result++;
        });

        $m->onHook('test1', function () use (&$result) {
            $result = 0;
        }, null, 1);

        $m->hook('test1'); // zero will be executed first, then increment
        $this->assertEquals(1, $result);
    }

    public function testMultiple()
    {
        $m = new HookMock();
        $result = 0;

        $m->onHook(['test1,test2', 'test3'], function () use (&$result) {
            $result++;
        });

        $m->hook('test1');
        $m->hook('test2');
        $m->hook('test3');
        $m->hook('test4');
        $this->assertEquals(3, $result);

        $m->removeHook('test2');
        $m->hook('test1');
        $m->hook('test2');
        $m->hook('test3');
        $m->hook('test4');

        $this->assertEquals(5, $result);
    }

    private $result = 0;

    public function tst($obj = null, $inc = 1)
    {
        if (is_null($obj)) {
            // because phpunit tries to execute this method
            return;
        }
        $this->result += $inc;
    }

    public function testCallable()
    {
        $m = new HookMock();
        $this->result = 0;

        $m->onHook('tst', $this);
        $m->hook('tst');

        $this->assertEquals(1, $this->result);

        $m->hook('tst', [5]);
        $this->assertEquals(6, $this->result);

        // Existing method - foo
        $m = new HookWithDynamicMethodMock();
        $m->onHook('foo', $m);
    }

    public function testCallableException1()
    {
        // unknown method
        $this->expectException(Exception::class);
        $m = new HookMock();
        $m->onHook('unknown_method', $m);
    }

    public function testCallableException2()
    {
        // not existing dynamic method
        $this->expectException(Exception::class);
        $m = new HookWithDynamicMethodMock();
        $m->onHook('unknown_method', $m);
    }

    public function testCallableException3()
    {
        // wrong 2nd argument
        $this->expectException(Exception::class);
        $m = new HookMock();
        $m->onHook('unknown_method', 'incorrect_param');
    }

    public function testHookException1()
    {
        // wrong 2nd argument
        $m = new HookMock();

        $result = '';
        $m->onHook('tst', function ($m, $arg) use (&$result) {
            $result .= $arg;
        });

        $m->hook('tst', 'parameter');

        $this->assertEquals('parameter', $result);
    }

    public function testOrder()
    {
        $m = new HookMock();
        $m->onHook('spot', function () {
            return 3;
        }, null, -1);
        $m->onHook('spot', function () {
            return 2;
        }, null, -5);
        $m->onHook('spot', function () {
            return 1;
        }, null, -5);

        $m->onHook('spot', function () {
            return 4;
        }, null, 0);
        $m->onHook('spot', function () {
            return 5;
        }, null, 0);

        $m->onHook('spot', function () {
            return 10;
        }, null, 1000);

        $m->onHook('spot', function () {
            return 6;
        }, null, 2);
        $m->onHook('spot', function () {
            return 7;
        }, null, 5);
        $m->onHook('spot', function () {
            return 8;
        });
        $m->onHook('spot', function () {
            return 9;
        }, null, 5);

        $ret = $m->hook('spot');

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $ret);
    }

    public function testMulti()
    {
        $obj = new HookMock();

        $mul = function ($obj, $a, $b) {
            return $a * $b;
        };

        $add = function ($obj, $a, $b) {
            return $a + $b;
        };

        $obj->onHook('test', $mul);
        $obj->onHook('test', $add);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertEquals([4, 4], $res1);

        $res2 = $obj->hook('test', [3, 3]);
        $this->assertEquals([9, 6], $res2);
    }

    public function testArgs()
    {
        $obj = new HookMock();

        $mul = function ($obj, $a, $b) {
            return $a * $b;
        };

        $add = function ($obj, $a, $b) {
            return $a + $b;
        };

        $pow = function ($obj, $a, $b, $power) {
            return pow($a, $power) + pow($b, $power);
        };

        $obj->onHook('test', $mul);
        $obj->onHook('test', $add);
        $obj->onHook('test', $pow, [2]);
        $obj->onHook('test', $pow, [7]);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertEquals([4, 4, 8, 256], $res1);

        $res2 = $obj->hook('test', [2, 3]);
        $this->assertEquals([6, 5, 13, 2315], $res2);
    }

    public function testReferences()
    {
        $obj = new HookMock();

        $inc = function ($obj, &$a) {
            $a++;
        };

        $obj->onHook('inc', $inc);
        $v = 1;
        $a = [&$v];
        $obj->hook('inc', $a);

        $this->assertEquals([2], $a);

        $obj = new HookMock();

        $inc = function ($obj, &$a) {
            $a++;
        };

        $v = 1;
        $obj->onHook('inc', $inc);
        $obj->hook('inc', [&$v]);

        $this->assertEquals(2, $v);
    }

    public function testDefaultMethod()
    {
        $obj = new HookMock();
        $obj->onHook('myCallback', $obj);
        $obj->hook('myCallback');

        $this->assertEquals(1, $obj->result);
    }

    public function testBreakHook()
    {
        $m = new HookMock();
        $m->result = 0;

        $inc = function ($obj) {
            $obj->result++;
            if ($obj->result == 2) {
                $obj->breakHook('stop');
            }
        };

        $m->onHook('inc', $inc);
        $m->onHook('inc', $inc);
        $m->onHook('inc', $inc);

        $ret = $m->hook('inc');
        $this->assertEquals(2, $m->result);
        $this->assertEquals('stop', $ret);
    }

    public function testExceptionInHook()
    {
        $this->expectException(Exception::class);
        $m = new HookMock();
        $m->result = 0;

        $inc = function ($obj) {
            throw new \atk4\core\Exception(['stuff went wrong']);
        };

        $m->onHook('inc', $inc);
        $ret = $m->hook('inc');
    }
}

// @codingStandardsIgnoreStart
class HookMock
{
    use HookTrait;

    public $result = 0;

    public function myCallback($obj)
    {
        $this->result++;
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
