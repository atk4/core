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
        \Closure::bind(fn () => $printer->printDefectTrace($defect), null, $resultPrinterClass)();
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
        $this->assertStringContainsString((string) $exception, $resNotWrapped);
        $this->assertStringContainsString((string) $innerException, $resNotWrapped);

        $staticClass = get_class(new class() {
            public static int $counter = 0;
        });
        if (++$staticClass::$counter > 2) {
            // allow this test to be run max. twice, new ExceptionWrapper() is leaking memory,
            // see https://github.com/sebastianbergmann/phpunit/blob/9.5.21/src/Framework/ExceptionWrapper.php#L112
            // https://github.com/sebastianbergmann/phpunit/pull/5012
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $res = $this->printAndReturnDefectTrace(ResultPrinter::class, new ExceptionWrapper($exception));
        $this->assertTrue(strlen($res) < strlen($resNotWrapped));
        if (\PHP_MAJOR_VERSION < 8) {
            // phpvfscomposer:// is not correctly filtered from stacktrace
            // by PHPUnit\Util\Filter::getFilteredStacktrace() method
            return;
        }
        $this->assertSame(
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
