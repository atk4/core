<?php

/**
 * Polyfill for phpunit < 9.0:
 * - include missing but equivalent classes in phpunit 9.0
 */

namespace atk4\core\AtkPhpunit
{
    // prevent StyleCI class rename
}

namespace PHPUnit\TextUI
{
    class DefaultResultPrinter extends ResultPrinter
    {
    }
}
