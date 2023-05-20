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

        self::assertSame('User Defined Entity', $a->readableCaption('userDefinedEntity'));
        self::assertSame('New NASA Module', $a->readableCaption('newNASA_module'));
        self::assertSame('This Is NASA My Big Bull Shit 123 Foo', $a->readableCaption('this\ _isNASA_MyBigBull shit_123\Foo'));

        self::assertSame('ID', $a->readableCaption('id'));
        self::assertSame('Account ID', $a->readableCaption('account_id'));
    }
}

class ReadableCaptionMock
{
    use ReadableCaptionTrait;
}
