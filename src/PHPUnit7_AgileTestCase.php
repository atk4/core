<?php

declare(strict_types=1);

namespace atk4\core;

use PHPUnit\Framework\TestCase;

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
class PHPUnit7_AgileTestCase extends TestCase
{
    public function runBare(): void
    {
        try {
            parent::runBare();
        } catch (Exception $e) {
            throw new PHPUnit7_AgileExceptionWrapper($e->getMessage(), 0, $e);
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
}
