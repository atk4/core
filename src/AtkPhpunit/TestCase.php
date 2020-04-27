<?php

namespace atk4\core\AtkPhpunit;

require_once __DIR__ . '/phpunit_polyfill.php';

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    public function runBare(): void
    {
        try {
            parent::runBare();
        } catch (Exception $e) {
            throw new ExceptionWrapper($e->getMessage(), 0, $e);
        }
    }

    /**
     * Calls protected method.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @return mixed
     */
    public function callProtected(object $obj, string $name, array $args = [])
    {
        return \Closure::bind(static function () use ($obj, $name, $args) {
            return $obj->{$name}(...$args);
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
    public function getProtected(object $obj, string $name)
    {
        return \Closure::bind(static function () use ($obj, $name) {
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

    /**
     * Add assertMatchesRegularExpression() method for phpunit < 9.0 for compatibility with PHP 7.2.
     *
     * @TODO Remove once PHP 7.2 support is not needed for testing anymore, ie. phpunit 9.0 can be used.
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            static::assertRegExp($pattern, $string, $message);
        }
    }
}
