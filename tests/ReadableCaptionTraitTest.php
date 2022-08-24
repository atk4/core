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

        static::assertSame('User Defined Entity', $a->readableCaption('userDefinedEntity'));
        static::assertSame('New NASA Module', $a->readableCaption('newNASA_module'));
        static::assertSame('This Is NASA My Big Bull Shit 123 Foo', $a->readableCaption('this\ _isNASA_MyBigBull shit_123\Foo'));

        static::assertSame('ID', $a->readableCaption('id'));
        static::assertSame('Account ID', $a->readableCaption('account_id'));
    }
}

class ReadableCaptionMock
{
    use ReadableCaptionTrait;
}
