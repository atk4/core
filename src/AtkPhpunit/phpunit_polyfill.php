<?php

namespace atk4\core\AtkPhpunit;

$phpunitVersion = 5;
if (class_exists(\PHPUnit\TextUI\DefaultResultPrinter::class)) {
    $phpunitVersion = 9;
} elseif (class_exists(\PHPUnit\Framework\TestCase::class)) {
    $phpunitVersion = 7;
}

if ($phpunitVersion <= 6) {
    require_once __DIR__ . '/phpunit6_polyfill.php';
} elseif ($phpunitVersion <= 8) {
    require_once __DIR__ . '/phpunit8_polyfill.php';
}
