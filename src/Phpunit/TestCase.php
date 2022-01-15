<?php

declare(strict_types=1);

namespace Atk4\Core\Phpunit;

use Atk4\Core\WarnDynamicPropertyTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
abstract class TestCase extends BaseTestCase
{
    use WarnDynamicPropertyTrait;

    protected function tearDown(): void
    {
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
