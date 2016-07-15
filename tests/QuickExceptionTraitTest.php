<?php

namespace atk4\core\tests;

use atk4\core\QuickExceptionTrait;

/**
 * @coversDefaultClass \atk4\core\QuickExceptionTrait
 */
class QuickExceptionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testConstructor()
    {
        $m = new QuickExceptionMock();

        // not implemented yet
        $this->assertEquals(null, $m->exception());
    }
}

// @codingStandardsIgnoreStart
class QuickExceptionMock
{
    use QuickExceptionTrait;
}
// @codingStandardsIgnoreEnd
