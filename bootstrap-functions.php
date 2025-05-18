<?php

declare(strict_types=1);

use Atk4\Core\DumpHelper;

/**
 * @param mixed $value
 */
function atk4_print_r($value): void
{
    $dumpHelper = new DumpHelper(); // @phpstan-ignore new.internalClass
    $dumpHelper->printReadable($value); // @phpstan-ignore method.internalClass
}
