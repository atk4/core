<?php

declare(strict_types=1);

use Atk4\Core\DumpHelper;

/**
 * @param mixed $value
 */
function atk4_print_r($value, int $maxDepth = 50): void
{
    $dumpHelper = new DumpHelper(); // @phpstan-ignore new.internalClass
    $dumpHelper->printReadable($value, $maxDepth); // @phpstan-ignore method.internalClass
}
