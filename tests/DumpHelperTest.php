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

    /**
     * @param mixed $value
     */
    private static function getRid(&$value): string
    {
        return \Closure::bind(static function () use (&$value) {
            return (new DumpHelper())->getRid($value);
        }, null, DumpHelper::class)();
    }

    /**
     * @dataProvider provideFindDuplicateOidsRidsCases
     *
     * @param \Closure(): array{mixed, array<int, positive-int>, array<string, positive-int>} $makeCaseFx
     */
    #[DataProvider('provideFindDuplicateOidsRidsCases')]
    public function testFindDuplicateOidsRids(\Closure $makeCaseFx, int $maxDepth = 50): void
    {
        [$value, $expectedDuplicateOids, $expectedDuplicateRids] = $makeCaseFx();

        $dumpHelper = new DumpHelper();
        $rootValue = &$value;
        $duplicateOids = [];
        $duplicateRids = [];
        \Closure::bind(static function () use ($dumpHelper, &$value, &$rootValue, $maxDepth, &$duplicateOids, &$duplicateRids) {
            $dumpHelper->findDuplicateOidsRids($value, $rootValue, $maxDepth, $duplicateOids, $duplicateRids);
        }, null, DumpHelper::class)();

        self::assertSame($value, $rootValue);
        self::assertSame(self::getRid($value), self::getRid($rootValue));
        self::assertSame($expectedDuplicateOids, $duplicateOids);
        self::assertSame($expectedDuplicateRids, $duplicateRids);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideFindDuplicateOidsRidsCases(): iterable
    {
        yield 'scalar' => [static function () {
            $v = 10.5;

            return [$v, [], [
                self::getRid($v) => 1,
            ]];
        }];

        yield 'object' => [static function () {
            $v = new \DateTime();

            return [$v, [
                spl_object_id($v) => 1,
            ], [
                self::getRid($v) => 1,
            ]];
        }];

        yield 'array with objects' => [static function () {
            $dt = new \DateTime();
            $dt2 = new \DateTime();
            $dtCopy = $dt;
            $arr = [&$dt, &$dt2, &$dt, &$dtCopy];

            return [$arr, [
                spl_object_id($dt) => 3,
                spl_object_id($dt2) => 1,
            ], [
                self::getRid($arr) => 1,
                self::getRid($dt) => 2,
                self::getRid($dt2) => 1,
                self::getRid($dtCopy) => 1,
            ]];
        }];

        yield 'array recursive' => [static function () {
            $v = false;
            $v2 = [&$v];
            $v2[] = &$v2;
            $arr = [&$v2];

            return [$arr, [], [
                self::getRid($arr) => 1,
                self::getRid($v2) => 2,
                self::getRid($v) => 1,
            ]];
        }];

        yield 'array recursive top' => [static function () {
            $v = false;
            $arr = [&$v];
            $arr[] = &$arr;

            return [$arr, [], [
                self::getRid($arr) => 2,
                self::getRid($v) => 1,
            ]];
        }];

        yield 'object recursive' => [static function () {
            $o = new QuietObjectWrapper(new \DateTime());
            \Closure::bind(static function () use (&$o) {
                $o->obj = &$o;
            }, null, QuietObjectWrapper::class)();
            $oCopy = $o;

            $v = false;
            $oDynamic = new \stdClass();
            $oDynamic->foo = &$v;
            $oDynamic->bar = &$oDynamic;
            $oDynamicCopy = $oDynamic;

            $arr = [&$o, &$o, &$oDynamic, &$oDynamic, &$oCopy, &$oDynamicCopy];

            return [$arr, [
                spl_object_id($o) => 4,
                spl_object_id($oDynamic) => 4,
            ], [
                self::getRid($arr) => 1,
                self::getRid($o) => 3,
                self::getRid($oDynamic) => 3,
                self::getRid($v) => 1,
                self::getRid($oCopy) => 1,
                self::getRid($oDynamicCopy) => 1,
            ]];
        }];

        yield 'array max depth 0' => [static function () {
            $dt = new \DateTime();
            $arr = [&$dt, [1]];

            return [$arr, [], [
                self::getRid($arr) => 1,
            ]];
        }, 0];

        yield 'array max depth -1' => [static function () {
            $dt = new \DateTime();
            $arr = [&$dt, [1]];

            return [$arr, [], [
                self::getRid($arr) => 1,
            ]];
        }, 0];

        yield 'array max depth 1' => [static function () {
            $dt = new \DateTime();
            $dt2 = new \DateTime();
            $v = [&$dt2, [1]];
            $arr = [&$dt, &$v];

            return [$arr, [
                spl_object_id($dt) => 1,
            ], [
                self::getRid($arr) => 1,
                self::getRid($dt) => 1,
                self::getRid($arr[1]) => 1,
            ]];
        }, 1];
    }
}
