<?php
namespace atk4\core\tests;

use atk4\core\HookTrait;

/**
 * @coversDefaultClass \atk4\data\Model
 */
class HookTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     */
    public function testBasic()
    {
        $m = new HookMock();
        $result = 0;

        $m->addHook('test1', function()use(&$result) {
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

        $m->addHook('test1', function()use(&$result) {
            $result++;
        });

        $m->addHook('test1', function()use(&$result) {
            $result=0;
        }, null, 1);


        $m->hook('test1'); // zero will be executed first, then increment
        $this->assertEquals(1, $result);
    }

    public function testMultiple()
    {
        $m = new HookMock();
        $result = 0;

        $m->addHook(['test1,test2','test3'], function()use(&$result) {
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

    private $result=0;
    public function test($obj=null, $inc=1){
        if (is_null($obj)) {
            // because phpunit tries to execute this method
            return;
        }
        $this->result+=$inc;
    }

    public function testCallable(){
        $m = new HookMock();
        $this->result = 0;

        $m->addHook('test', $this);
        $m->hook('test');

        $this->assertEquals(1, $this->result);

        $m->hook('test',[5]);
        $this->assertEquals(6, $this->result);
    }

    public function testOrder() {
        $m = new HookMock();
        $m->addHook('spot', function(){ return 3; }, null, -1);
        $m->addHook('spot', function(){ return 2; }, null, -5);
        $m->addHook('spot', function(){ return 1; }, null, -5);

        $m->addHook('spot', function(){ return 4; }, null, 0);
        $m->addHook('spot', function(){ return 5; }, null, 0);

        $m->addHook('spot', function(){ return 10; }, null, 1000);

        $m->addHook('spot', function(){ return 6; }, null, 2);
        $m->addHook('spot', function(){ return 7; }, null, 5);
        $m->addHook('spot', function(){ return 8; });
        $m->addHook('spot', function(){ return 9; }, null, 5);

        $ret=$m->hook('spot');

        $this->assertEquals([1,2,3,4,5,6,7,8,9,10], $ret);
    }

    public function testMulti() {
        $obj = new HookMock();

        $mul = function($obj, $a, $b) { 
            return $a*$b;
        };

        $add = function($obj, $a, $b) { 
            return $a+$b;
        };

        $obj->addHook('test', $mul);
        $obj->addHook('test', $add);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertEquals([4, 4], $res1);

        $res2 = $obj->hook('test', [3, 3]);
        $this->assertEquals([9, 6], $res2);
    }

    public function testArgs() {
        $obj = new HookMock();

        $mul = function($obj, $a, $b) { 
            return $a*$b;
        };

        $add = function($obj, $a, $b) { 
            return $a+$b;
        };

        $pow = function($obj, $a, $b, $power) {
            return pow($a, $power) + pow($b, $power);
        };

        $obj->addHook('test', $mul);
        $obj->addHook('test', $add);
        $obj->addHook('test', $pow, [2]);
        $obj->addHook('test', $pow, [7]);

        $res1 = $obj->hook('test', [2, 2]);
        $this->assertEquals([4, 4, 8, 256], $res1);

        $res2 = $obj->hook('test', [2, 3]);
        $this->assertEquals([6, 5, 13, 2315], $res2);

    }

    public function testDefaultMethod() {
        $obj = new HookMock();
        $obj->addHook('myCallback', $obj);
        $obj->hook('myCallback');

        $this->assertEquals(1, $obj->result);
    }
}

class HookMock {
    use HookTrait;

    public $result = 0;

    function myCallback($obj) {
        $this->result++;
    }
}

