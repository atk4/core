<?php

namespace atk4\core\AtkPhpunit;

$phpunitVersionStr = class_exists(\PHPUnit\Runner\Version::class)
        ? \PHPUnit\Runner\Version::id()
        : \PHPUnit_Runner_Version::id();
$phpunitVersion = (float)preg_replace('~^(\d+(?:\.\d+)?).*~s', '$1', $phpunitVersionStr);

if ($phpunitVersion < 6) {
    require_once __DIR__ . '/phpunit5_polyfill.php';
}

if ($phpunitVersion < 9) {
    require_once __DIR__ . '/phpunit8_polyfill.php';
}
