<?php

/**
 * Polyfill for phpunit < 9.0:
 * - include missing but equivalent classes in phpunit 9.0
 */

namespace PHPUnit\TextUI {
    class DefaultResultPrinter extends ResultPrinter
    {
    }
}
