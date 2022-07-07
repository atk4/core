<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Phpunit;

use Atk4\Core\Phpunit\TestCase;

class TestCaseTest extends TestCase
{
    /** @var int */
    private static $providerCallCounter = 0;

    private function coverCoverageFromProvider(): void
    {
        ++self::$providerCallCounter;
    }

    /**
     * @dataProvider provideProviderCoverage1
     * @dataProvider provideProviderCoverage2
     */
    public function testProviderCoverage1(string $v): void
    {
        $this->markTestIncomplete('test II');
    }

    public function provideProviderCoverage1(): \Traversable
    {
        yield ['a'];
    }

    public function provideProviderCoverage2(): \Traversable
    {
        yield ['x'];
        $this->coverCoverageFromProvider();
        yield ['y'];
    }
}
