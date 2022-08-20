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
    private object $objectTypedNoDefault;

    private static int $providerCoverageCallCounter = 0;

    protected function createAndCountObject(): object
    {
        $destructFx = function () {
            --self::$activeObjectsCounter;
        };

        $object = new class($destructFx) {
            public \Closure $destructFx;

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
     * @dataProvider provideProviderAb
     */
    public function testObjectsAreReleasedAfterTest(string $v): void
    {
        $this->assertSame(0, self::$activeObjectsCounter);
        $this->assertSame('default', $this->object);
        $this->assertNull($this->objectTyped);
        $reflectionProperty = new \ReflectionProperty($this, 'objectTypedNoDefault');
        $reflectionProperty->setAccessible(true);
        $this->assertFalse($reflectionProperty->isInitialized($this));

        if ($v === 'a') {
            $o = $this->createAndCountObject();
            $this->assertSame(1, self::$activeObjectsCounter);
            $o = null;
            $this->assertSame(0, self::$activeObjectsCounter);
            $this->object = $this->createAndCountObject();
            $this->assertSame(1, self::$activeObjectsCounter);

            $this->object = $this->createAndCountObject();
            $this->objectTyped = $this->createAndCountObject();
            $this->objectTypedNoDefault = $this->createAndCountObject();
            $this->assertNotNull($this->objectTypedNoDefault); // remove once https://github.com/phpstan/phpstan/issues/7818 is fixed
            $this->assertSame(3, self::$activeObjectsCounter);
        }
    }

    public function testObjectsAreReleasedFromUncaughtException(): void
    {
        $this->assertSame(0, self::$activeObjectsCounter);

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
        $this->assertSame(2, self::$activeObjectsCounter);

        $e2 = null;
        try {
            $this->onNotSuccessfulTest($e);
        } catch (\Error $e2) {
        }
        $this->assertSame($e, $e2);

        $this->assertSame(0, self::$activeObjectsCounter);
    }

    public function provideProviderAb(): \Traversable
    {
        yield ['a'];
        yield ['b'];
    }

    /**
     * @dataProvider provideProviderAb
     * @dataProvider provideProviderCoverage
     */
    public function testProviderCoverage(string $v): void
    {
        if ($v === 'y') {
            $this->assertSame(2, self::$providerCoverageCallCounter);
        }
        $this->assertTrue(in_array($v, ['a', 'b', 'x', 'y'], true));
    }

    public function provideProviderCoverage(): \Traversable
    {
        yield ['x'];
        ++self::$providerCoverageCallCounter;
        yield ['y'];
    }

    /**
     * @dataProvider provideProviderAb
     */
    public function testCoverageImplForDoesNotPerformAssertions(string $v): void
    {
        $this->assertFalse($this->doesNotPerformAssertions());

        $staticClass = get_class(new class() {
            public static int $counter = 0;
        });
        if ($v === 'a' && ++$staticClass::$counter > 1) {
            // allow TestCase::runBare() to be run more than once
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $this->assertTrue($this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything());

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
