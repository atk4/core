<?php

namespace atk4\core\AtkPhpunit;

use PHPUnit\Framework\TestFailure;
use PHPUnit\TextUI\ResultPrinter;

require_once __DIR__ . '/phpunit6_polyfill.php';

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class ResultPrinter extends ResultPrinter
{
    /**
     * Prints trace info.
     *
     * @param \PHPUnit_Framework_TestFailure $defect
     */
    protected function printDefectTrace(TestFailure $defect): void
    {
        $e = $defect->thrownException();
        if (!$e instanceof ExceptionWrapper) {
            parent::printDefectTrace($defect);

            return;
        }
        $this->write((string) $e);

        $p = $e->getPrevious();

        if ($p instanceof \atk4\core\Exception) {
            $this->write($p->getColorfulText());
        }
    }
}
