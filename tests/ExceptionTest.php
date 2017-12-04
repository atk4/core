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

        $m = new Exception('PrevError');
        $m = new Exception('TestIt', 123, $m);
        $m->addMoreInfo('a1', 222);
        $m->addMoreInfo('a2', 333);

        // params
        $this->assertEquals(['a1' => 222, 'a2' => 333], $m->getParams());

        // get colorful text
        $ret = $m->getColorfulText();
        $this->assertRegExp('/TestIt/', $ret);
        $this->assertRegExp('/PrevError/', $ret);
        $this->assertRegExp('/333/', $ret);

        // get console HTLM
        $ret = $m->getHTMLText();
        $this->assertRegExp('/TestIt/', $ret);
        $this->assertRegExp('/PrevError/', $ret);
        $this->assertRegExp('/333/', $ret);

        // get colorful text
        $ret = $m->getHTML();
        $this->assertRegExp('/TestIt/', $ret);
        $this->assertRegExp('/PrevError/', $ret);
        $this->assertRegExp('/333/', $ret);

        // to string
        $ret = $m->toString(1);
        $this->assertEquals('1', $ret);

        $ret = $m->toString('abc');
        $this->assertEquals('"abc"', $ret);

        $ret = $m->toString(new \StdClass());
        $this->assertEquals('Object stdClass', $ret);

        $a = new TrackableMock2();
        $a->name = 'foo';
        $ret = $m->toString($a);
        $this->assertEquals('atk4\core\tests\TrackableMock2 (foo)', $ret);
    }

    public function testMore()
    {
        $m = new \Exception('Classic Exception');

        $m = new Exception('atk4 exception', null, $m);
        $m->setMessage('bumbum');

        $ret = $m->getColorfulText();
        $this->assertRegExp('/Classic/', $ret);
        $this->assertRegExp('/bumbum/', $ret);

        // get colorful text
        $ret = $m->getHTML();
        $this->assertRegExp('/Classic/', $ret);
        $this->assertRegExp('/bumbum/', $ret);
    }
}

// @codingStandardsIgnoreStart
class TrackableMock2
{
    use TrackableTrait;
}
// @codingStandardsIgnoreEnd
