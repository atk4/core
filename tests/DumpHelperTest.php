<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DumpHelper;
use Atk4\Core\ExceptionRenderer\Html as HtmlExceptionRenderer;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\QuietObjectWrapper;

class DumpHelperTest extends TestCase
{
    private static function relativizePath(string $path): string
    {
        return \Closure::bind(static function () use ($path) {
            return (new HtmlExceptionRenderer((new \ReflectionClass(\Exception::class))->newInstanceWithoutConstructor()))->tryRelativizePathsInString($path);
        }, null, HtmlExceptionRenderer::class)();
    }

    /**
     * @dataProvider provideGetObjectPropertiesCases
     */
    #[DataProvider('provideGetObjectPropertiesCases')]
    public function testGetObjectProperties(object $value, array $expectedResult): void
    {
        $dumpHelper = new DumpHelper();
        $result = \Closure::bind(static fn () => $dumpHelper->getObjectProperties($value), null, DumpHelper::class)();

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideGetObjectPropertiesCases(): iterable
    {
        yield 'no properties' => [new \stdClass(), []];

        yield 'sort' => [new class extends \stdClass {
            public $bar = 'x';
            public $bar2 = 'y';
            public $bar10 = 'z';
        }, [
            'bar' => 'x',
            'bar10' => 'z',
            'bar2' => 'y',
        ]];

        $o = new class extends \stdClass {
            public $bar2 = 'x';
        };
        $o->foo = 1;
        $o->bar = null;
        yield 'dynamic property' => [$o, [
            'bar' => null,
            'bar2' => 'x',
            'foo' => 1,
        ]];

        $dt = new \DateTime();
        yield 'private property' => [new QuietObjectWrapper($dt), [
            'obj' => $dt,
        ]];

        yield 'redeclared private property' => [new class($dt) extends QuietObjectWrapper {
            private bool $obj;
            private bool $a;
            protected bool $b;
            public bool $c;

            /**
             * @param T $obj
             */
            public function __construct(object $obj)
            {
                parent::__construct($obj);

                $this->obj = true;
                $this->a = true;
            }
        }, [
            'a' => true,
            'b' => null,
            'c' => null,
            'obj:' . QuietObjectWrapper::class => $dt,
            'obj:' . QuietObjectWrapper::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 21) => true,
        ]];

        $exception = new \Exception();
        yield 'redeclared protected property' => [new class($exception) extends HtmlExceptionRenderer {
            public \Throwable $exception;
        }, [
            'adapter' => null,
            'exception' => $exception,
            'output' => '',
            'parentException' => null,
        ]];
    }
}
