<?php

declare(strict_types=1);

namespace Atk4\Core\AtkPhpunit;

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Calls protected method.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
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
        \Closure::bind(static function () use ($obj, &$value, $byReference) {
            if ($byReference) {
                $obj->{$name} = &$value;
            } else {
                $obj->{$name} = $value;
            }
        }, null, $obj)();

        return $this;
    }

    /**
     * Fake test. Otherwise phpunit gives warning that there are no tests in here.
     *
     * @doesNotPerformAssertions
     */
    public function testFake(): void
    {
    }
}
