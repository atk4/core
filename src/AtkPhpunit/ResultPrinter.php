<?php

declare(strict_types=1);

namespace atk4\core\AtkPhpunit;

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
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
