<?php

namespace atk4\core\AtkPhpunit;

/**
 * Polyfill for phpunit < 7.0 to map commonly used classes to equivalent phpunit 7.0 namespaced names.
 */

if (class_exists(\PHPUnit\Framework\TestCase::class)) { // do nothing for phpunit 7.0 or higher
    return;
}

namespace PHPUnit\Framework;

class Exception extends \PHPUnit_Framework_Exception
{
}

namespace PHPUnit\TextUI;

class ResultPrinter extends \PHPUnit_TextUI_ResultPrinter
{
}

namespace PHPUnit\Framework;

class TestCase extends \PHPUnit_Framework_TestCase
{
}

namespace PHPUnit\Framework;

class TestFailure extends \PHPUnit_Framework_TestFailure
{
}
