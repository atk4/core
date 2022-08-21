<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\ReadableCaptionTrait;

class ReadableCaptionTraitTest extends TestCase
{
    public function testReadableCaption(): void
    {
        $a = new ReadableCaptionMock();

        $this->assertSame('User Defined Entity', $a->readableCaption('userDefinedEntity'));
        $this->assertSame('New NASA Module', $a->readableCaption('newNASA_module'));
        $this->assertSame('This Is NASA My Big Bull Shit 123 Foo', $a->readableCaption('this\ _isNASA_MyBigBull shit_123\Foo'));

        $this->assertSame('ID', $a->readableCaption('id'));
        $this->assertSame('Account ID', $a->readableCaption('account_id'));
    }
}

class ReadableCaptionMock
{
    use ReadableCaptionTrait;
}
