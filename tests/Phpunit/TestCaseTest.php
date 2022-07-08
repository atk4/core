<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Phpunit;

use Atk4\Core\Phpunit\TestCase;
use PHPUnit\Framework\TestCase as PhpunitTestCase;
use PHPUnit\Runner\BaseTestRunner;

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
    public function testProviderCoverage(string $v): void
    {
        if ($v === 'y') {
            $this->assertSame(2, self::$providerCallCounter);
        }
        $this->assertTrue(in_array($v, ['a', 'b', 'x', 'y'], true));
    }

    public function provideProviderCoverage1(): \Traversable
    {
        yield ['a'];
        yield ['b'];
    }

    public function provideProviderCoverage2(): \Traversable
    {
        yield ['x'];
        $this->coverCoverageFromProvider();
        yield ['y'];
    }

    /**
     * @dataProvider provideProviderCoverage1
     */
    public function testCoverageImplForDoesNotPerformAssertions(string $v): void
    {
        $this->assertFalse($this->doesNotPerformAssertions());
        $this->assertTrue($this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything());

        if ($v === 'b') {
            // make sure TestResult::$beStrictAboutTestsThatDoNotTestAnything is reset
            // after this test using phpunit AfterTestHook
            return;
        }

        $testStatusOrig = \Closure::bind(fn () => $this->status, $this, PhpunitTestCase::class)();
        \Closure::bind(fn () => $this->status = BaseTestRunner::STATUS_PASSED, $this, PhpunitTestCase::class)();
        try {
            \Closure::bind(fn () => $this->doesNotPerformAssertions = true, $this, PhpunitTestCase::class)();
            try {
                $this->tearDown();
            } finally {
                \Closure::bind(fn () => $this->doesNotPerformAssertions = false, $this, PhpunitTestCase::class)();
            }
        } finally {
            \Closure::bind(fn () => $this->status = $testStatusOrig, $this, PhpunitTestCase::class)();
        }
        $this->assertFalse($this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCoverageImplForTestMarkedAsIncomplete(): void
    {
        $testStatusOrig = \Closure::bind(fn () => $this->status, $this, PhpunitTestCase::class)();
        \Closure::bind(fn () => $this->status = BaseTestRunner::STATUS_INCOMPLETE, $this, PhpunitTestCase::class)();
        try {
            $this->tearDown();
        } finally {
            \Closure::bind(fn () => $this->status = $testStatusOrig, $this, PhpunitTestCase::class)();
        }
    }
}
