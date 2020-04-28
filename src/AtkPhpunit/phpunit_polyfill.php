<?php

declare(strict_types=1);

namespace atk4\core\AtkPhpunit;

if (!class_exists(\PHPUnit\Runner\Version::class) && version_compare(\PHPUnit_Runner_Version::id(), '6') < 0) {
    require_once __DIR__ . '/phpunit5_polyfill.php';
}

if (version_compare(\PHPUnit\Runner\Version::id(), '9') < 0) {
    require_once __DIR__ . '/phpunit8_polyfill.php';
}
