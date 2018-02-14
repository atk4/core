<?php

namespace atk4\core\tests;

use atk4\core\NameTrait;
use atk4\core\SessionTrait;

/**
 * @coversDefaultClass \atk4\core\SessionTrait
 */
class SessionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException     Exception
     */
    public function testException1()
    {
        // when try to start session without NameTrait
        $m = new SessionWithoutNameMock();
        $m->startSession();
    }

    /**
     * Test constructor.
     */
    public function testConstructor()
    {
        $m = new SessionMock();

        $this->assertEquals(false, isset($_SESSION));
        $m->startSession();
        $this->assertEquals(true, isset($_SESSION));
        $m->destroySession();
        $this->assertEquals(false, isset($_SESSION));
    }

    /**
     * Test memorize().
     */
    public function testMemorize()
    {
        $m = new SessionMock();
        $m->name = 'test';

        // value as string
        $m->memorize('foo', 'bar');
        $this->assertEquals('bar', $_SESSION['o'][$m->name]['foo']);

        // value as null
        $m->memorize('foo', null);
        $this->assertEquals(null, $_SESSION['o'][$m->name]['foo']);

        // value as callable
        $m->memorize('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $_SESSION['o'][$m->name]['foo']);

        // value as object
        $o = new \StdClass();
        $m->memorize('foo', $o);
        $this->assertEquals($o, $_SESSION['o'][$m->name]['foo']);

        $m->destroySession();
    }

    /**
     * Test learn(), recall(), forget().
     */
    public function testLearnRecallForget()
    {
        $m = new SessionMock();
        $m->name = 'test';

        // value as string
        $m->learn('foo', 'bar');
        $this->assertEquals('bar', $m->recall('foo'));

        $m->learn('foo', 'qwerty');
        $this->assertEquals('bar', $m->recall('foo'));

        $m->forget('foo');
        $this->assertEquals('undefined', $m->recall('foo', 'undefined'));

        // value as callback
        $m->learn('foo', function ($key) {
            return $key.'_bar';
        });
        $this->assertEquals('foo_bar', $m->recall('foo'));

        $m->learn('foo_2', 'another');
        $this->assertEquals('another', $m->recall('foo_2'));

        $v = $m->recall('foo_3', function ($key) {
            return $key.'_bar';
        });
        $this->assertEquals('foo_3_bar', $v);
        $this->assertEquals('undefined', $m->recall('foo_3', 'undefined'));

        $m->forget();
        $this->assertEquals('undefined', $m->recall('foo', 'undefined'));
        $this->assertEquals('undefined', $m->recall('foo_2', 'undefined'));
        $this->assertEquals('undefined', $m->recall('foo_3', 'undefined'));
    }
}

// @codingStandardsIgnoreStart
class SessionMock
{
    use SessionTrait;
    use NameTrait;
}
class SessionWithoutNameMock
{
    use SessionTrait;
}
// @codingStandardsIgnoreEnd
