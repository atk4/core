<?php

/**
 * Polyfill for phpunit < 6.0:
 * - map commonly used classes to equivalent phpunit 6.0 namespaced names
 */

namespace atk4\core\AtkPhpunit
{
    // prevent class rename by StyleCI
}

namespace PHPUnit\Framework
{
    // defined since phpunit 5.4.0
    // see https://github.com/sebastianbergmann/phpunit/tree/5.4.0/src/ForwardCompatibility
    if (!class_exists(TestCase::class)) {
        class TestCase extends \PHPUnit_Framework_TestCase
        {
        }
    }

    class Exception extends \PHPUnit_Framework_Exception
    {
    }

    class TestFailure extends \PHPUnit_Framework_TestFailure
    {
    }
}

namespace PHPUnit\TextUI
{
    class ResultPrinter extends \PHPUnit_TextUI_ResultPrinter
    {
    }
}
