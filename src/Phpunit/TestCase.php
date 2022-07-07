<?php

declare(strict_types=1);

namespace Atk4\Core\Phpunit;

use Atk4\Core\WarnDynamicPropertyTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Runner\TestListenerAdapter;
use PHPUnit\Util\Test as TestUtil;
use SebastianBergmann\CodeCoverage\CodeCoverage;

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
abstract class TestCase extends BaseTestCase
{
    use WarnDynamicPropertyTrait;

    protected function setUp(): void
    {
        // rerun data providers to fix coverage when coverage for test files is enabled
        // https://github.com/sebastianbergmann/php-code-coverage/issues/920
        $staticClass = get_class(new class() {
            /** @var array<string, true> */
            public static $processedMethods = [];
        });
        $classRefl = new \ReflectionClass(static::class);
        foreach ($classRefl->getMethods() as $methodRefl) {
            $methodDoc = $methodRefl->getDocComment();
            if ($methodDoc !== false && preg_match_all('~@dataProvider[ \t]+([\w\x7f-\xff]+::)?([\w\x7f-\xff]+)~', $methodDoc, $matchesAll, \PREG_SET_ORDER)) {
                foreach ($matchesAll as $matches) {
                    $providerClassRefl = $matches[1] === '' ? $classRefl : new \ReflectionClass($matches[1]);
                    $providerMethodRefl = $providerClassRefl->getMethod($matches[2]);
                    $key = $providerClassRefl->getName() . '::' . $providerMethodRefl->getName();
                    if (!isset($staticClass::$processedMethods[$key])) {
                        $staticClass::$processedMethods[$key] = true;
                        $providerInstance = $providerClassRefl->newInstanceWithoutConstructor();
                        $provider = $providerMethodRefl->invoke($providerInstance);
                        if (!is_array($provider)) {
                            // yield all provider data
                            iterator_to_array($provider);
                        }
                    }
                }
            }
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // remove once https://github.com/sebastianbergmann/phpunit/issues/4705 is fixed
        foreach (array_keys(array_diff_key(get_object_vars($this), get_class_vars(BaseTestCase::class))) as $k) {
            $this->{$k} = \PHP_MAJOR_VERSION < 8
                ? (new \ReflectionProperty(static::class, $k))->getDeclaringClass()->getDefaultProperties()[$k]
                : (null ?? (new \ReflectionProperty(static::class, $k))->getDefaultValue()); // @phpstan-ignore-line for PHP 7.x
        }

        // once PHP 8.0 support is dropped, needed only once, see:
        // https://github.com/php/php-src/commit/b58d74547f7700526b2d7e632032ed808abab442
        if (\PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }
        gc_collect_cycles();

        // fix coverage when no assertion is expected
        // https://github.com/sebastianbergmann/phpunit/pull/5010
        if ($this->getStatus() === BaseTestRunner::STATUS_PASSED
            && $this->getNumAssertions() === 0 && $this->doesNotPerformAssertions()
            && $this->getTestResultObject()->isStrictAboutTestsThatDoNotTestAnything()
        ) {
            $testResult = $this->getTestResultObject();
            $afterHookTest = new class($testResult) implements AfterTestHook {
                /** @var TestResult */
                public $testResult;

                public function __construct(TestResult $testResult)
                {
                    $this->testResult = $testResult;
                }

                public function executeAfterTest(string $test, float $time): void
                {
                    $this->testResult->beStrictAboutTestsThatDoNotTestAnything(true);
                }
            };
            $alreadyAdded = false;
            foreach (\Closure::bind(fn () => $testResult->listeners, null, TestResult::class)() as $listener) {
                if ($listener instanceof TestListenerAdapter) {
                    foreach (\Closure::bind(fn () => $listener->hooks, null, TestListenerAdapter::class)() as $hook) {
                        if (get_class($hook) === get_class($afterHookTest)) {
                            $alreadyAdded = true;
                        }
                    }
                }
            }
            if (!$alreadyAdded) {
                $testListenerAdapter = new TestListenerAdapter();
                $testListenerAdapter->add($afterHookTest);
                $testResult->addListener($testListenerAdapter);
            }
            $testResult->beStrictAboutTestsThatDoNotTestAnything(false);
        }

        // fix coverage for skipped/incomplete tests
        // based on https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L830
        // and https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L857
        if (in_array($this->getStatus(), [BaseTestRunner::STATUS_SKIPPED, BaseTestRunner::STATUS_INCOMPLETE], true)) {
            $coverage = $this->getTestResultObject()->getCodeCoverage();
            if ($coverage !== null) {
                $coverageId = \Closure::bind(fn () => $coverage->currentId, null, CodeCoverage::class)();
                if ($coverageId !== null) { // @phpstan-ignore-line https://github.com/sebastianbergmann/php-code-coverage/pull/923
                    $linesToBeCovered = TestUtil::getLinesToBeCovered(static::class, $this->getName(false));
                    $linesToBeUsed = TestUtil::getLinesToBeUsed(static::class, $this->getName(false));
                    $coverage->stop(true, $linesToBeCovered, $linesToBeUsed);
                    $coverage->start($coverageId);
                }
            }
        }
    }

    /**
     * Calls protected method.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function &callProtected(object $obj, string $name, ...$args)
    {
        return \Closure::bind(static function &() use ($obj, $name, $args) {
            $objRefl = new \ReflectionClass($obj);
            if ($objRefl
                ->getMethod(!$objRefl->hasMethod($name) && $objRefl->hasMethod('__call') ? '__call' : $name)
                ->returnsReference()) {
                return $obj->{$name}(...$args);
            }

            $v = $obj->{$name}(...$args);

            return $v;
        }, null, $obj)();
    }

    /**
     * Returns protected property value.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @return mixed
     */
    public function &getProtected(object $obj, string $name)
    {
        return \Closure::bind(static function &() use ($obj, $name) {
            return $obj->{$name};
        }, null, $obj)();
    }

    /**
     * Sets protected property value.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function setProtected(object $obj, string $name, &$value, bool $byReference = false)
    {
        \Closure::bind(static function () use ($obj, $name, &$value, $byReference) {
            if ($byReference) {
                $obj->{$name} = &$value;
            } else {
                $obj->{$name} = $value;
            }
        }, null, $obj)();

        return $this;
    }
}
