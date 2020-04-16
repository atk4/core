<?php

namespace atk4\core\AtkPhpunit;

if (version_compare(class_exists(\PHPUnit\Runner\Version::class) ? \PHPUnit\Runner\Version::id() : \PHPUnit_Runner_Version::id(), 6) < 0) {
    require_once __DIR__ . '/phpunit5_polyfill.php';
}

if (version_compare(\PHPUnit\Runner\Version::id(), 9) < 0) {
    require_once __DIR__ . '/phpunit8_polyfill.php';
}
