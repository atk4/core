<?php

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\ReadableCaptionTrait;

/**
 * @coversDefaultClass \atk4\core\ReadableCaptionTrait
 */
class ReadableCaptionTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test readableCaption method.
     */
    public function testReadableCaption()
    {
        $a = new ReadableCaptionMock();

        $this->assertEquals('User Defined Entity', $a->readableCaption('userDefinedEntity'));
        $this->assertEquals('New NASA Module', $a->readableCaption('newNASA_module'));
        $this->assertEquals('This Is NASA My Big Bull Shit 123 Foo', $a->readableCaption('this\\ _isNASA_MyBigBull shit_123\Foo'));
    }
}

// @codingStandardsIgnoreStart
class ReadableCaptionMock
{
    use ReadableCaptionTrait;
}
// @codingStandardsIgnoreEnd
