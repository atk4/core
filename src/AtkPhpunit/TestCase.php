<?php

declare(strict_types=1);

namespace atk4\core\AtkPhpunit;

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
            if ((new \ReflectionClass($obj))->getMethod($name)->returnsReference()) {
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
     * Fake test. Otherwise Travis gives warning that there are no tests in here.
     *
     * @doesNotPerformAssertions
     */
    public function testFake(): void
    {
    }
}
