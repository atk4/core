<?php

declare(strict_types=1);

use Atk4\Core\DumpHelper;

/**
 * Improved version of native print_r() function.
 *
 * Objects and array references are printed only once.
 *
 * @param mixed       $value
 * @param int<0, max> $maxDepth
 */
function atk4_print_r($value, int $maxDepth = 50): void
{
    $dumpHelper = new DumpHelper(); // @phpstan-ignore new.internalClass
    $dumpHelper->printReadable($value, $maxDepth); // @phpstan-ignore method.internalClass
}
