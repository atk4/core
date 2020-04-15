<?php

namespace atk4\core\AtkPhpunit;

use PHPUnit\Framework\TestFailure;

require_once __DIR__ . '/phpunit_polyfill.php';

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
{
    /**
     * Prints trace info.
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
