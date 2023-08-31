<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Phpunit;

use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;
use PHPUnit\Framework\TestCase as PhpunitTestCase;
use PHPUnit\Runner\BaseTestRunner;

class TestCaseTest extends TestCase
{
    private static int $activeObjectsCounter = 0;

    /** @var object|'default' */
    private $object = 'default';
    private ?object $objectTyped = null;
    public object $objectTypedNoDefault; // cannot be private/protected until https://github.com/php/php-src/issues/9389 is implemented and no unset is needed for property uninitialization, valid for burn testing only

    private static int $providerCoverageCallCounter = 0;

    protected function createAndCountObject(): object
    {
        $destructFx = static function () {
            --self::$activeObjectsCounter;
        };

        $object = new class($destructFx) {
            public \Closure $destructFx;

            /**
             * @param \Closure(): void $destructFx
             */
            public function __construct(\Closure $destructFx)
            {
                $this->destructFx = $destructFx;
            }

            public function __destruct()
            {
                ($this->destructFx)();
            }
        };
        ++self::$activeObjectsCounter;

        return $object;
    }

    /**
     * @dataProvider provideProviderAbCases
     */
    public function testObjectsAreReleasedAfterTest(string $v): void
    {
        self::assertSame(0, self::$activeObjectsCounter);
        self::assertSame('default', $this->object);
        self::assertNull($this->objectTyped);
        $reflectionProperty = new \ReflectionProperty($this, 'objectTypedNoDefault');
        $reflectionProperty->setAccessible(true);
        self::assertFalse($reflectionProperty->isInitialized($this));

        if ($v === 'a') {
            $o = $this->createAndCountObject();
            self::assertSame(1, self::$activeObjectsCounter);
            $o = null;
            self::assertSame(0, self::$activeObjectsCounter);
            $this->object = $this->createAndCountObject();
            self::assertSame(1, self::$activeObjectsCounter);

            $this->object = $this->createAndCountObject();
            $this->objectTyped = $this->createAndCountObject();
            // @ is needed because of https://github.com/php/php-src/issues/9389 for burn testing
            @$this->objectTypedNoDefault = $this->createAndCountObject();
            self::assertNotNull($this->objectTypedNoDefault); // remove once https://github.com/phpstan/phpstan/issues/7818 is fixed
            self::assertSame(3, self::$activeObjectsCounter);
        }
    }

    public function testObjectsAreReleasedFromUncaughtException(): void
    {
        self::assertSame(0, self::$activeObjectsCounter);

        $throwFx = function (object $arg): never {
            throw (new Exception())
                ->addMoreInfo('x', $this->createAndCountObject());
        };
        $e = null;
        try {
            $throwFx($this->createAndCountObject());
        } catch (\Exception $e) {
            $e = new \Error('wrap', 0, $e);
        }
        self::assertSame(2, self::$activeObjectsCounter);

        $e2 = null;
        try {
            $this->onNotSuccessfulTest($e);
        } catch (\Error $e2) {
        }
        self::assertSame($e, $e2);

        self::assertSame(0, self::$activeObjectsCounter);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public function provideProviderAbCases(): iterable
    {
        yield ['a'];
        yield ['b'];
    }

    /**
     * @dataProvider provideProviderAbCases
     * @dataProvider provideProviderCoverageCases
     */
    public function testProviderCoverage(string $v): void
    {
        if ($v === 'y') {
            self::assertSame(2, self::$providerCoverageCallCounter);
        }
        self::assertTrue(in_array($v, ['a', 'b', 'x', 'y'], true));
    }

    /**
     * @return iterable<list<mixed>>
     */
    public function provideProviderCoverageCases(): iterable
    {
        yield ['x'];
        ++self::$providerCoverageCallCounter;
        yield ['y'];
    }

    /**
     * @dataProvider provideProviderAbCases
     */
    public function testCoverageImplForDoesNotPerformAssertions(string $v): void
    {
        self::assertFalse($this->doesNotPerformAssertions());

        $staticClass = get_class(new class() {
            public static int $counter = 0;
        });
        if ($v === 'a' && ++$staticClass::$counter > 1) {
            // allow TestCase::runBare() to be run more than once
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        self::assertTrue($this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything());

        if ($v === 'b') {
            // make sure TestResult::$beStrictAboutTestsThatDoNotTestAnything is reset
            // after this test by AfterTestHook hook added by our TestCase
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

        self::assertFalse($this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything());
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
