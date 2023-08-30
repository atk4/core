<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Phpunit;

use Atk4\Core\Exception;
use Atk4\Core\Phpunit\ResultPrinter;
use Atk4\Core\Phpunit\TestCase;
use PHPUnit\Framework\ExceptionWrapper;
use PHPUnit\Framework\TestFailure;

class ResultPrinterTest extends TestCase
{
    /**
     * @param class-string<ResultPrinter> $resultPrinterClass
     */
    private function printAndReturnDefectTrace($resultPrinterClass, \Throwable $exception): string
    {
        $defect = new TestFailure($this, $exception);
        $stream = fopen('php://memory', 'w+');
        $printer = new $resultPrinterClass($stream);
        \Closure::bind(static fn () => $printer->printDefectTrace($defect), null, $resultPrinterClass)();
        fseek($stream, 0);

        return stream_get_contents($stream);
    }

    public function testBasic(): void
    {
        $innerException = new \Error('Inner Exception');
        $exception = (new Exception('My exception', 0, $innerException))
            ->addMoreInfo('x', 'foo')
            ->addMoreInfo('y', ['bar' => 2.4, [], [[1]]]);

        $resNotWrapped = $this->printAndReturnDefectTrace(ResultPrinter::class, $exception);
        self::assertStringContainsString((string) $exception, $resNotWrapped);
        self::assertStringContainsString((string) $innerException, $resNotWrapped);

        $res = $this->printAndReturnDefectTrace(ResultPrinter::class, new ExceptionWrapper($exception));
        self::assertTrue(strlen($res) < strlen($resNotWrapped));
        if (\PHP_MAJOR_VERSION < 8) {
            // phpvfscomposer:// is not correctly filtered from stacktrace
            // by PHPUnit\Util\Filter::getFilteredStacktrace() method
            return;
        }
        self::assertSame(
            <<<'EOF'
                Atk4\Core\Exception: My exception
                  x: 'foo'
                  y: [
                      'bar': 2.4,
                      0: [],
                      1: [
                          ...
                        ]
                    ]

                self.php:32

                Caused by
                Error: Inner Exception

                self.php:31
                EOF . "\n", // NL in the string is not parsed by Netbeans, see https://github.com/apache/netbeans/issues/4345
            str_replace(__FILE__, 'self.php', $res)
        );
    }
}
