<?php

namespace atk4\core\tests;

use atk4\core\SessionTrait;

/**
 * @coversDefaultClass \atk4\core\SessionTrait
 */
class SessionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testConstructor()
    {
        $m = new SessionMock();

        // not implemented yet
        $this->assertEquals(null, $m->memorize());
        $this->assertEquals(null, $m->learn());
        $this->assertEquals(null, $m->recall());
        $this->assertEquals(null, $m->forget());

        $this->markTestIncomplete();
    }
}

// @codingStandardsIgnoreStart
class SessionMock
{
    use SessionTrait;
}
// @codingStandardsIgnoreEnd
