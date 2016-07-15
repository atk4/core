<?php

namespace atk4\core\tests;

use atk4\core\Exception;
use atk4\core\TrackableTrait;

/**
 * @coversDefaultClass \atk4\core\Exception
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test getColorfulText() and toString().
     */
    public function testColorfulText()
    {
        $m = new Exception(['TestIt', 'a1' => 111, 'a2' => 222]);

        // params
        $this->assertEquals(['a1' => 111, 'a2' => 222], $m->getParams());
        
        // get colorful text
        $ret = $m->getColorfulText();
        $this->assertRegExp("/TestIt/", $ret);

        // to string
        $ret = $m->toString(1);
        $this->assertEquals('1', $ret);

        $ret = $m->toString('abc');
        $this->assertEquals('"abc"', $ret);

        $ret = $m->toString(new \StdClass());
        $this->assertEquals('Object StdClass', $ret);

        $a = new TrackableMock();
        $a->name = 'foo';
        $ret = $m->toString($a);
        $this->assertEquals('atk4\core\tests\TrackableMock (foo)', $ret);
    }
}

// @codingStandardsIgnoreStart
class TrackableMock
{
    use TrackableTrait;
}
// @codingStandardsIgnoreEnd
