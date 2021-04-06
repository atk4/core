<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AtkPhpunit;
use Atk4\Core\ReadableCaptionTrait;

/**
 * @coversDefaultClass \Atk4\Core\ReadableCaptionTrait
 */
class ReadableCaptionTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test readableCaption method.
     */
    public function testReadableCaption()
    {
        $a = new ReadableCaptionMock();

        $this->assertSame('User Defined Entity', $a->readableCaption('userDefinedEntity'));
        $this->assertSame('New NASA Module', $a->readableCaption('newNASA_module'));
        $this->assertSame('This Is NASA My Big Bull Shit 123 Foo', $a->readableCaption('this\\ _isNASA_MyBigBull shit_123\Foo'));
    }
}

class ReadableCaptionMock
{
    use ReadableCaptionTrait;
}
