<?php

namespace atk4\core;

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class PHPUnit_AgileResultPrinter extends \PHPUnit_TextUI_ResultPrinter
{
    /**
     * Prints trace info.
     *
     * @param \PHPUnit_Framework_TestFailure $defect
     */
    protected function printDefectTrace(\PHPUnit_Framework_TestFailure $defect)
    {
        $e = $defect->thrownException();
        if (!$e instanceof \atk4\core\PHPUnit_AgileExceptionWrapper) {
            return parent::printDefectTrace($defect);
        }
        $this->write((string) $e);

        $p = $e->getPrevious();

        if ($p instanceof \atk4\core\Exception) {
            $this->write($p->getColorfulText());
        }
    }
}
