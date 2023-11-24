<?php

declare(strict_types=1);

namespace Atk4\Core\Phpunit;

use Atk4\Core\WarnDynamicPropertyTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Runner\BaseTestRunner;
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

        // release objects from TestCase instance as it is never released
        // https://github.com/sebastianbergmann/phpunit/issues/4705
        $classes = [];
        $class = static::class;
        do {
            $classes[] = $class;
            $class = get_parent_class($class);
        } while ($class !== BaseTestCase::class);
        unset($class);
        foreach (array_reverse($classes) as $class) {
            \Closure::bind(function () use ($class) {
                foreach (array_keys(array_intersect_key(array_diff_key(get_object_vars($this), get_class_vars(BaseTestCase::class)), get_class_vars($class))) as $k) {
                    $reflectionProperty = new \ReflectionProperty($class, $k);
                    if (\PHP_MAJOR_VERSION < 8
                        ? array_key_exists($k, $reflectionProperty->getDeclaringClass()->getDefaultProperties())
                        : (null ?? $reflectionProperty->hasDefaultValue()) // @phpstan-ignore-line for PHP 7.x
                    ) {
                        $this->{$k} = \PHP_MAJOR_VERSION < 8
                            ? $reflectionProperty->getDeclaringClass()->getDefaultProperties()[$k]
                            : (null ?? $reflectionProperty->getDefaultValue()); // @phpstan-ignore-line for PHP 7.x
                    } else {
                        unset($this->{$k});
                    }
                }
            }, $this, $class)();
        }

        // once PHP 8.0 support is dropped, needed only once, see:
        // https://github.com/php/php-src/commit/b58d74547f7700526b2d7e632032ed808abab442
        if (\PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }
        gc_collect_cycles();

        // fix coverage for skipped/incomplete tests
        // based on https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L830
        // and https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L857
        if (in_array($this->getStatus(), [BaseTestRunner::STATUS_SKIPPED, BaseTestRunner::STATUS_INCOMPLETE], true)) {
            $coverage = $this->getTestResultObject()->getCodeCoverage();
            if ($coverage !== null) {
                $coverageId = \Closure::bind(static fn () => $coverage->currentId, null, CodeCoverage::class)();
                if ($coverageId !== null) {
                    $linesToBeCovered = TestUtil::getLinesToBeCovered(static::class, $this->getName(false));
                    $linesToBeUsed = TestUtil::getLinesToBeUsed(static::class, $this->getName(false));
                    $coverage->stop(true, $linesToBeCovered, $linesToBeUsed);
                    $coverage->start($coverageId);
                }
            }
        }
    }

    private function releaseObjectsFromExceptionTrace(\Throwable $e): void
    {
        $replaceObjectsFx = static function ($v) use (&$replaceObjectsFx) {
            if (is_object($v) && !$v instanceof \DateTimeInterface) {
                $v = 'object of ' . get_debug_type($v) . ' class unreferenced by ' . self::class;
            } elseif (is_array($v)) {
                $v = array_map($replaceObjectsFx, $v);
            }

            return $v;
        };

        $traceReflectionProperty = new \ReflectionProperty($e instanceof \Exception ? \Exception::class : \Error::class, 'trace');
        $traceReflectionProperty->setAccessible(true);
        $traceReflectionProperty->setValue($e, $replaceObjectsFx($traceReflectionProperty->getValue($e)));
        if ($e instanceof \Atk4\Core\Exception) {
            $paramsReflectionProperty = new \ReflectionProperty(\Atk4\Core\Exception::class, 'params');
            $paramsReflectionProperty->setAccessible(true);
            $paramsReflectionProperty->setValue($e, $replaceObjectsFx($paramsReflectionProperty->getValue($e)));
        }

        if ($e->getPrevious() !== null) {
            $this->releaseObjectsFromExceptionTrace($e->getPrevious());
        }
    }

    protected function onNotSuccessfulTest(\Throwable $e): void
    {
        // release objects from uncaught exception as it is never released
        $this->releaseObjectsFromExceptionTrace($e);

        // once PHP 8.0 support is dropped, needed only once, see:
        // https://github.com/php/php-src/commit/b58d74547f7700526b2d7e632032ed808abab442
        if (\PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }
        gc_collect_cycles();

        parent::onNotSuccessfulTest($e);
    }
}
