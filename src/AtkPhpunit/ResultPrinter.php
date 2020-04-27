<?php

namespace atk4\core\AtkPhpunit;

require_once __DIR__ . '/phpunit_polyfill.php';

// trait is needed for phpunit < 6.0 only, fix ResultPrinterTrait::printDefectTrace() method header mismatch
trait ResultPrinterTrait
{
    /**
     * Prints trace info.
     */
    protected function printDefectTrace($defect): void
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

if (version_compare(\PHPUnit\Runner\Version::id(), '6') < 0) {
    /**
     * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
     */
    class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
    {
        use ResultPrinterTrait {
            printDefectTrace as _printDefectTrace;
        }

        /**
         * Prints trace info.
         */
        protected function printDefectTrace(\PHPUnit_Framework_TestFailure $defect): void
        {
            $this->_printDefectTrace($defect);
        }
    }
} else {
    /**
     * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
     */
    class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
    {
        use ResultPrinterTrait;
    }
}
