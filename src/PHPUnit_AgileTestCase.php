<?php

namespace atk4\core;

class PHPUnit_AgileTestCase extends \PHPUnit_Framework_TestCase
{
    public function runBare()
    {
        try {
            return parent::runBare();
        } catch (Exception $e) {
            throw new PHPUnit_AgileExceptionWrapper($e->getMessage(), 0, $e);
        }
    }

    /**
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     */
    public function callProtected($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * NOTE: this method must only be used for low-level functionality, not
     * for general test-scripts.
     */
    public function getProtected($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getProperty($name);
        $method->setAccessible(true);

        return $method->getValue($obj);
    }
}
