<?php

namespace atk4\core;

/**
 * Generic TestCase for PHPUnit tests for ATK4 repos.
 */
class PHPUnit_AgileTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Runs the bare test sequence.
     *
     * @return null
     */
    public function runBare()
    {
        try {
            return parent::runBare();
        } catch (Exception $e) {
            throw new PHPUnit_AgileExceptionWrapper($e->getMessage(), 0, $e);
        }
    }

    /**
     * Calls protected method.
     *
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     *
     * @param object $obj
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function callProtected($obj, $name, array $args = [])
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
     * @param object $obj
     * @param string $name
     *
     * @return mixed
     */
    public function getProtected($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getProperty($name);
        $method->setAccessible(true);

        return $method->getValue($obj);
    }
}
