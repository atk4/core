<?php

declare(strict_types=1);

namespace Atk4\Core\Phpunit;

use Atk4\Core\Exception as CoreException;
use Atk4\Core\WarnDynamicPropertyTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Metadata\Api\CodeCoverage as CodeCoverageMetadata;
use PHPUnit\Metadata\Parser\Registry as MetadataRegistry;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Runner\CodeCoverage;
use PHPUnit\Util\Test as TestUtil;
use SebastianBergmann\CodeCoverage\CodeCoverage as CodeCoverageRaw;

if (\PHP_VERSION_ID >= 80100) {
    trait Phpunit9xTestCaseTrait
    {
        #[\Override]
        protected function onNotSuccessfulTest(\Throwable $e): never
        {
            $this->_onNotSuccessfulTest($e);
        }
    }
} else {
    trait Phpunit9xTestCaseTrait
    {
        #[\Override]
        protected function onNotSuccessfulTest(\Throwable $e): void
        {
            $this->_onNotSuccessfulTest($e);
        }
    }
}

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
abstract class TestCase extends BaseTestCase
{
    use Phpunit9xTestCaseTrait;
    use WarnDynamicPropertyTrait;

    final public static function isPhpunit9x(): bool
    {
        return (new \ReflectionClass(self::class))->hasMethod('getStatus');
    }

    #[\Override]
    protected function setUp(): void
    {
        // rerun data providers to fix coverage when coverage for test files is enabled
        // https://github.com/sebastianbergmann/php-code-coverage/issues/920
        $staticClass = get_class(new class() {
            /** @var array<string, true> */
            public static $processedMethods = [];
        });

        $metadataDataProviders = [];
        if (self::isPhpunit9x()) {
            $annotations = TestUtil::parseTestMethodAnnotations(static::class, $this->getName(false));
            foreach ($annotations['method']['dataProvider'] ?? [] as $dataProviderAnnotation) {
                preg_match('~^([\w\x7f-\xff]+::)?([\w\x7f-\xff]+)~', $dataProviderAnnotation, $matches);
                $metadataDataProviders[] = [$matches[1] === '' ? static::class : $matches[1], $matches[2]];
            }
        } else {
            $metadataDataProviders = MetadataRegistry::parser()->forClassAndMethod(static::class, $this->name())->isDataProvider();
        }

        foreach ($metadataDataProviders as $metadataDataProvider) {
            $providerClassRefl = new \ReflectionClass(self::isPhpunit9x() ? $metadataDataProvider[0] : $metadataDataProvider->className());
            $providerMethodRefl = $providerClassRefl->getMethod(self::isPhpunit9x() ? $metadataDataProvider[1] : $metadataDataProvider->methodName());
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

        parent::setUp();
    }

    #[\Override]
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
                    if (\PHP_MAJOR_VERSION === 7
                        ? array_key_exists($k, $reflectionProperty->getDeclaringClass()->getDefaultProperties())
                        : (null ?? $reflectionProperty->hasDefaultValue()) // @phpstan-ignore-line for PHP 7.x
                    ) {
                        $this->{$k} = \PHP_MAJOR_VERSION === 7
                            ? $reflectionProperty->getDeclaringClass()->getDefaultProperties()[$k]
                            : (null ?? $reflectionProperty->getDefaultValue()); // @phpstan-ignore-line for PHP 7.x
                    } else {
                        unset($this->{$k});
                    }
                }
            }, $this, $class)();
        }

        // once PHP 8.0 support is dropped, needed only once, see:
        // https://github.com/php/php-src/commit/b58d74547f
        if (\PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }
        gc_collect_cycles();

        // fix coverage for skipped/incomplete tests
        // based on https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L830 https://github.com/sebastianbergmann/phpunit/blob/10.4.2/src/Framework/TestRunner.php#L154
        // and https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/TestResult.php#L857 https://github.com/sebastianbergmann/phpunit/blob/10.4.2/src/Framework/TestRunner.php#L178
        if (self::isPhpunit9x() ? in_array($this->getStatus(), [BaseTestRunner::STATUS_SKIPPED, BaseTestRunner::STATUS_INCOMPLETE], true) : $this->status()->isSkipped() || $this->status()->isIncomplete()) {
            $coverage = self::isPhpunit9x() ? $this->getTestResultObject()->getCodeCoverage() : (CodeCoverage::instance()->isActive() ? CodeCoverage::instance() : null);
            if ($coverage !== null) {
                $coverageId = self::isPhpunit9x() ? \Closure::bind(static fn () => $coverage->currentId, null, CodeCoverageRaw::class)() : (\Closure::bind(static fn () => $coverage->collecting, null, CodeCoverage::class)() ? $this : null);
                if ($coverageId !== null) {
                    $linesToBeCovered = self::isPhpunit9x() ? TestUtil::getLinesToBeCovered(static::class, $this->getName(false)) : (new CodeCoverageMetadata())->linesToBeCovered(static::class, $this->name());
                    $linesToBeUsed = self::isPhpunit9x() ? TestUtil::getLinesToBeUsed(static::class, $this->getName(false)) : (new CodeCoverageMetadata())->linesToBeUsed(static::class, $this->name());
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
        if ($e instanceof CoreException) {
            $paramsReflectionProperty = new \ReflectionProperty(CoreException::class, 'params');
            $paramsReflectionProperty->setAccessible(true);
            $paramsReflectionProperty->setValue($e, $replaceObjectsFx($paramsReflectionProperty->getValue($e)));
        }

        if ($e->getPrevious() !== null) {
            $this->releaseObjectsFromExceptionTrace($e->getPrevious());
        }
    }

    /**
     * @return never
     */
    protected function _onNotSuccessfulTest(\Throwable $e): void
    {
        // release objects from uncaught exception as it is never released
        $this->releaseObjectsFromExceptionTrace($e);

        // once PHP 8.0 support is dropped, needed only once, see:
        // https://github.com/php/php-src/commit/b58d74547f
        if (\PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }
        gc_collect_cycles();

        parent::onNotSuccessfulTest($e);
    }
}
