<?php

namespace atk4\core\AtkPhpunit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/phpunit6_polyfill.php';

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
class TestCase extends TestCase
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
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public function callProtected(object $obj, string $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * Returns protected property value.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public function getProtected(object $obj, string $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getProperty($name);
        $method->setAccessible(true);

        return $method->getValue($obj);
    }

    /**
     * Fake test. Otherwise Travis gives warning that there are no tests in here.
     */
    public function testFake(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Add assertMatchesRegularExpression() method for phpunit >= 8.0 < 9.0 for compatibility with PHP 7.2.
     *
     * @TODO Remove once PHP 7.2 support is not needed for testing anymore.
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
