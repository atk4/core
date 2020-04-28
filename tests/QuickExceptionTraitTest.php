<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\QuickExceptionTrait;

/**
 * @coversDefaultClass \atk4\core\QuickExceptionTrait
 */
class QuickExceptionTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
    public function testConstructor()
    {
        $m = new QuickExceptionMock();

        // not implemented yet
        $this->assertNull($m->exception());
    }
}

// @codingStandardsIgnoreStart
class QuickExceptionMock
{
    use QuickExceptionTrait;
}
// @codingStandardsIgnoreEnd
