<?php

declare(strict_types=1);

/**
 * Polyfill for phpunit < 9.0:
 * - include missing but equivalent classes in phpunit 9.0.
 */

namespace atk4\core\AtkPhpunit
{
    // prevent class rename by CS fixer
}

namespace PHPUnit\TextUI
{
    class DefaultResultPrinter extends ResultPrinter
    {
    }
}
