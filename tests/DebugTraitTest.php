<?php

namespace atk4\core\tests;

use atk4\core\DebugTrait;

/**
 * @coversDefaultClass \atk4\core\DebugTrait
 */
class DebugTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test debug().
     */
    public function testDebug()
    {
        $m = new DebugMock();

        $this->assertEquals(false, $m->debug);

        $m->debug();
        $this->assertEquals(true, $m->debug);

        $m->debug(false);
        $this->assertEquals(false, $m->debug);

        $m->debug(true);
        $this->assertEquals(true, $m->debug);

        $m->debug(false)->debug('switch on');
        $this->assertEquals(false, $m->debug);
    }
}

// @codingStandardsIgnoreStart
class DebugMock
{
    use DebugTrait;
}
// @codingStandardsIgnoreEnd
