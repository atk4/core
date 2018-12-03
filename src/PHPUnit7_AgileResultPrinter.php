<?php

namespace atk4\core;

use PHPUnit\Framework\TestFailure;
use PHPUnit\TextUI\ResultPrinter;

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class PHPUnit7_AgileResultPrinter extends ResultPrinter
{
    /**
     * Prints trace info.
     *
     * @param \PHPUnit_Framework_TestFailure $defect
     */
    protected function printDefectTrace(TestFailure $defect): void
    {
        $e = $defect->thrownException();
        if (!$e instanceof \atk4\core\PHPUnit7_AgileExceptionWrapper) {
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
