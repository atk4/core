<?php

declare(strict_types=1);

namespace Atk4\Core\Phpunit;

use Atk4\Core\Exception;
use Atk4\Core\ExceptionRenderer\RendererAbstract;
use PHPUnit\Framework\ExceptionWrapper;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Util\Filter;

/**
 * Custom PHPUnit ResultPrinter for atk4 repos.
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
            if ($e->getOriginalException() !== null) { // original exception is not available when run with process isolation
                $string .= $this->atkExceptionParamsToString($e->getOriginalException()); // @phpstan-ignore-line
            }
        }

        $trace = Filter::getFilteredStacktrace($e);
        if ($trace) {
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
            $valueStr = RendererAbstract::toSafeString($value, true);
            $string .= '  ' . $param . ': ' . str_replace("\n", "\n" . '    ', $valueStr) . "\n";
        }

        return $string;
    }
}
