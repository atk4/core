<?php

/**
 * Polyfill for phpunit < 7.0:
 * - map commonly used classes to equivalent phpunit 7.0 namespaced names
 */

namespace atk4\core\AtkPhpunit
{
    // prevent StyleCI class rename
}

namespace PHPUnit\Framework
{
    class Exception extends \PHPUnit_Framework_Exception
    {
    }

    class TestCase extends \PHPUnit_Framework_TestCase
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
