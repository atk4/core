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
     * @dataProvider provideProviderCoverage
     */
    public function testProviderCoverage(string $v): void
    {
        $this->assertGreaterThan(1, self::$providerCallCounter);
        $this->assertTrue(in_array($v, ['x', 'y'], true));
    }

    public function provideProviderCoverage(): \Traversable
    {
        yield ['x'];
        $this->coverCoverageFromProvider();
        yield ['y'];
    }
}
