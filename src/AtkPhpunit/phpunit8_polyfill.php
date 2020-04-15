<?php

namespace atk4\core\AtkPhpunit {
    /**
     * Polyfill for phpunit < 9.0 to include missing but equivalent classes in phpunit 9.0.
     */
    if (class_exists(\PHPUnit\TextUI\DefaultResultPrinter::class)) { // do nothing for phpunit 9.0 or higher
        return;
    }
}

namespace PHPUnit\TextUI {
    class DefaultResultPrinter extends ResultPrinter
    {
    }
}
