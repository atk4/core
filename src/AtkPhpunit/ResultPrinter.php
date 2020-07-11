<?php

declare(strict_types=1);

namespace atk4\core\AtkPhpunit;

use atk4\core\Exception;
use PHPUnit\Framework\ExceptionWrapper;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Util\Filter;

/**
 * Generic ResultPrinter for PHPUnit tests of ATK4 repos.
 */
class ResultPrinter extends \PHPUnit\TextUI\DefaultResultPrinter
{
    protected function printDefectTrace(TestFailure $defect): void
    {
        $e = $defect->thrownException();
        if ($e instanceof ExceptionWrapper) {
            $this->write($this->phpunitExceptionWrapperToString($e));

            return;
        }

        parent::printDefectTrace($defect);
    }

    /**
     * @see based on https://github.com/sebastianbergmann/phpunit/blob/899db927169682058ed8875c3d0c7f30c1fa4ed2/src/Framework/ExceptionWrapper.php#L49
     */
    private function phpunitExceptionWrapperToString(ExceptionWrapper $e): string
    {
        $string = TestFailure::exceptionToString($e);

        if (is_a($e->getClassName(), Exception::class, true)) {
            $string .= $this->atkExceptionParamsToString($e->getOriginalException());
        }

        if ($trace = Filter::getFilteredStacktrace($e)) {
            $string .= "\n" . $trace;
        }

        if ($e->getPreviousWrapped()) {
            $string .= "\nCaused by\n" . $this->phpunitExceptionWrapperToString($e->getPreviousWrapped());
        }

        return $string;
    }

    private function atkExceptionParamsToString(Exception $e): string
    {
        $string = '';
        foreach ($e->getParams() as $param => $value) {
            $valueStr = \atk4\core\ExceptionRenderer\RendererAbstract::toSafeString($value, true);
            $string .= '  ' . $param . ': ' . str_replace("\n", "\n" . '  ', $valueStr) . "\n";
        }

        return $string;
    }
}
