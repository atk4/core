<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DumpHelper;
use Atk4\Core\ExceptionRenderer\Html as HtmlExceptionRenderer;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\QuietObjectWrapper;
use PHPUnit\Framework\Attributes\DataProvider;

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
     *
     * @param array<string, mixed> $expectedResult
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
            /** @var string */
            public $bar = 'x';
            /** @var string */
            public $bar2 = 'y';
            /** @var string */
            public $bar10 = 'z';
        }, [
            'bar' => 'x',
            'bar10' => 'z',
            'bar2' => 'y',
        ]];

        $o = new class extends \stdClass {
            /** @var string */
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
            private bool $obj; // @phpstan-ignore property.onlyWritten
            private bool $a; // @phpstan-ignore property.onlyWritten
            protected bool $b;
            public bool $c;

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
            'obj:' . QuietObjectWrapper::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 18) => true,
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

        yield 'array recursion' => [static function () {
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

        yield 'array recursion top' => [static function () {
            $v = false;
            $arr = [&$v];
            $arr[] = &$arr;

            return [$arr, [], [
                self::getRid($arr) => 2,
                self::getRid($v) => 1,
            ]];
        }];

        yield 'object recursion' => [static function () {
            $o = new QuietObjectWrapper(new \DateTime());
            \Closure::bind(static function () use (&$o) {
                $o->obj = &$o; // @phpstan-ignore assign.propertyType
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

    /**
     * @dataProvider providePrintReadableCases
     *
     * @param \Closure(): array{mixed, string} $makeCaseFx
     */
    #[DataProvider('providePrintReadableCases')]
    public function testPrintReadable(\Closure $makeCaseFx, ?int $maxDepth = null): void
    {
        [$value, $expectedOutput] = $makeCaseFx();

        ob_start();
        $dumpHelper = new DumpHelper();
        if ($maxDepth === null) {
            $dumpHelper->printReadable($value);
        } else {
            $dumpHelper->printReadable($value, $maxDepth);
        }
        $output = ob_get_clean();

        self::assertStringEndsWith("\n", $output);
        self::assertSame($expectedOutput, substr($output, 0, -1));

        ob_start();
        if ($maxDepth === null) {
            atk4_print_r($value);
        } else {
            atk4_print_r($value, $maxDepth);
        }
        $output2 = ob_get_clean();

        self::assertSame($output, $output2);
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function providePrintReadableCases(): iterable
    {
        yield [static fn () => [null, 'null']];

        yield [static fn () => [false, 'false']];
        yield [static fn () => [true, 'true']];

        yield [static fn () => [fopen('php://memory', 'r+'), 'resource<stream>']];
        yield [static function () {
            $handle = fopen('php://memory', 'r+');
            fclose($handle);

            return [$handle, 'resource<*closed*>'];
        }];

        yield [static fn () => [0, '0']];
        yield [static fn () => [5, '5']];
        yield [static fn () => [-5, '-5']];
        yield [static fn () => [999, '999']];
        yield [static fn () => [1_000, '1_000']];
        yield [static fn () => [2_001_002_003, '2_001_002_003']];
        yield [static fn () => [-2_001_002_003, '-2_001_002_003']];
        yield [static fn () => [\PHP_INT_MAX, \PHP_INT_SIZE === 4 ? '2_147_483_647' : '9_223_372_036_854_775_807']];
        yield [static fn () => [\PHP_INT_MIN, \PHP_INT_SIZE === 4 ? '-2_147_483_648' : '-9_223_372_036_854_775_808']];

        yield [static fn () => [0.0, '0.0']];
        yield [static fn () => [-0.0, '-0.0']];
        yield [static fn () => [5.0, '5.0']];
        yield [static fn () => [-5.0, '-5.0']];
        yield [static fn () => [8.202343767574732, '8.202343767574732']];
        yield [static fn () => [99_999_999_999_999_980.0, '99_999_999_999_999_980.0']];
        yield [static fn () => [100_000_000_000_000_000.0, '1.0E+17']];
        yield [static fn () => [0.000005, '5.0E-6']];
        yield [static fn () => [-0.000005, '-5.0E-6']];
        yield [static fn () => [\INF, 'INF']];
        yield [static fn () => [-\INF, '-INF']];
        yield [static fn () => [\NAN, 'NAN']];

        yield [static fn () => ['', '\'\'']];
        yield [static fn () => ['0', '\'0\'']];
        yield [static fn () => ['foo bar', '\'foo bar\'']];
        yield [static fn () => ['<img src="x" />', '\'<img src="x" />\'']];
        yield [static fn () => ['foo\'bar', '\'foo\\\'bar\'']];
        yield [static fn () => ['foo\bar', '\'foo\bar\'']];
        yield [static fn () => ['foo\\\'bar', '\'foo\\\\\\\'bar\'']];
        yield [static fn () => ['foo\\\\\'bar', '\'foo\\\\\\\\\\\'bar\'']];
        yield [static fn () => ["a\nb", "<<<'EOD'\n    a\n    b\n    EOD"]];
        yield [static fn () => ["a\rb\r\n", "<<<'EOD'\n    a\r    b\r\n    \n    EOD"]];

        yield [static fn () => [[], 'empty-array []']];
        yield [static fn () => [['foo' => true, 'bar' => true], <<<'EOD'
            array<string, true> [
                'foo' => true,
                'bar' => true
            ]
            EOD]];
        yield 'false and true union' => [static fn () => [[false, true, 1], <<<'EOD'
            list<bool|int> [
                false,
                true,
                1
            ]
            EOD]];
        yield 'non-list and list union' => [static fn () => [[['foo' => true], [true], []], <<<'EOD'
            list<array> [
                array<string, true> [
                    'foo' => true
                ],
                list<true> [
                    true
                ],
                empty-array []
            ]
            EOD]];

        yield [static function () {
            $o = new \stdClass();

            return [$o, \stdClass::class . '#' . spl_object_id($o) . ' {}'];
        }];
        yield [static function () {
            $o = new class {};

            return [$o, 'class@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 2) . '#' . spl_object_id($o) . ' {}'];
        }];
        yield [static function () {
            $dt = new \DateTime('2013-02-20 20:00:12 UTC');
            $o = new QuietObjectWrapper($dt);

            return [$o, sprintf(
                <<<'EOD'
                    %s {
                        'obj': %s {}
                    }
                    EOD,
                QuietObjectWrapper::class . '#' . spl_object_id($o),
                \DateTime::class . '#' . spl_object_id($dt),
            )];
        }];
        yield [static function () {
            $dt = new \DateTime('2013-02-20 20:00:12 UTC');
            $o = new class($dt) extends QuietObjectWrapper {};

            return [$o, sprintf(
                <<<'EOD'
                    %s {
                        'obj': %s {}
                    }
                    EOD,
                QuietObjectWrapper::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 8) . '#' . spl_object_id($o),
                \DateTime::class . '#' . spl_object_id($dt),
            )];
        }];

        yield 'deduplicate array references' => [static function () {
            $arr = [true];

            return [[&$arr, &$arr, $arr, [&$arr]], <<<'EOD'
                list<list> [
                    &0 list<true> [
                        true
                    ],
                    &0 list<true> *deduplicated*,
                    list<true> [
                        true
                    ],
                    list<list> [
                        &0 list<true> *deduplicated*
                    ]
                ]
                EOD];
        }];
        yield 'deduplicate objects' => [static function () {
            $dt = new \DateTime('2013-02-20 20:00:12 UTC');
            $dt2 = new \DateTime('2013-02-20 20:00:12 UTC');
            $o = new QuietObjectWrapper($dt);

            return [[$dt, $dt, $dt2, [$dt], $o, $o], sprintf(
                <<<'EOD'
                    list<Atk4\Core\QuietObjectWrapper|DateTime|list> [
                        %s {},
                        %1$s *deduplicated*,
                        %s {},
                        list<DateTime> [
                            %1$s *deduplicated*
                        ],
                        %s {
                            'obj': %1$s *deduplicated*
                        },
                        %3$s *deduplicated*
                    ]
                    EOD,
                \DateTime::class . '#' . spl_object_id($dt),
                \DateTime::class . '#' . spl_object_id($dt2),
                QuietObjectWrapper::class . '#' . spl_object_id($o),
            )];
        }];
        yield 'track references for all native types' => [static function () {
            $null = null;
            $bool = true;
            $int = 1;
            $float = 1.0;
            $str = 'x';
            $arr = [];
            $o = new \stdClass();
            $resource = fopen('php://memory', 'r+');

            $arr2 = [$null, $bool, $int, $float, $str, $arr, $o, $resource];

            return [[
                [&$null, &$bool, &$int, &$float, &$str, &$arr, &$o, &$resource],
                &$arr2,
                [&$null, &$bool, &$int, &$float, &$str, &$arr, &$o, &$resource],
                &$arr2,
            ], sprintf(
                <<<'EOD'
                    list<list> [
                        list<float|int|list|null|resource|stdClass|string|true> [
                            &0 null,
                            &1 true,
                            &2 1,
                            &3 1.0,
                            &4 'x',
                            &5 empty-array [],
                            &6 %s {},
                            &7 resource<stream>
                        ],
                        &8 list<float|int|list|null|resource|stdClass|string|true> [
                            null,
                            true,
                            1,
                            1.0,
                            'x',
                            empty-array [],
                            %1$s *deduplicated*,
                            resource<stream>
                        ],
                        list<float|int|list|null|resource|stdClass|string|true> [
                            &0 null,
                            &1 true,
                            &2 1,
                            &3 1.0,
                            &4 'x',
                            &5 empty-array *deduplicated*,
                            &6 %1$s *deduplicated*,
                            &7 resource<stream>
                        ],
                        &8 list<float|int|list|null|resource|stdClass|string|true> *deduplicated*
                    ]
                    EOD,
                \stdClass::class . '#' . spl_object_id($o),
            )];
        }];
        yield 'track references thru expanded object' => [static function () {
            $v = true;
            $v2 = [&$v];
            $o = new \stdClass();
            $o->foo = $v2;
            $o->bar = &$v2;
            $o->baz = [&$v2];

            return [[&$v2, &$v, $o, [&$v2, &$v, $o], &$o], sprintf(
                <<<'EOD'
                    list<list|stdClass|true> [
                        &0 list<true> [
                            &1 true
                        ],
                        &1 true,
                        %s {
                            'bar': &0 list<true> *deduplicated*,
                            'baz': list<list> [
                                &0 list<true> *deduplicated*
                            ],
                            'foo': list<true> [
                                &1 true
                            ]
                        },
                        list<list|stdClass|true> [
                            &0 list<true> *deduplicated*,
                            &1 true,
                            %1$s *deduplicated*
                        ],
                        %1$s *deduplicated*
                    ]
                    EOD,
                \stdClass::class . '#' . spl_object_id($o),
            )];
        }];
        yield 'track array recursion' => [static function () {
            $arr = [false];
            $arr[] = &$arr;

            // TODO & below should not be needed
            return [[&$arr], <<<'EOD'
                list<list> [
                    &0 list<false|list> [
                        false,
                        &0 list<false|list> *recursion*
                    ]
                ]
                EOD];
        }];
        yield 'track array recursion top' => [static function () {
            $arr = [false];
            $arr[] = &$arr;

            return [$arr, <<<'EOD'
                &0? list<false|list> [
                    false,
                    &0? list<false|list> *recursion*
                ]
                EOD];
        }];
        yield 'track object recursion' => [static function () {
            $o = new QuietObjectWrapper(new \DateTime());
            \Closure::bind(static function () use (&$o) {
                $o->obj = &$o; // @phpstan-ignore assign.propertyType
            }, null, QuietObjectWrapper::class)();

            $oDynamic = new \stdClass();
            $oDynamic->foo = false;
            $oDynamic->bar = &$oDynamic;

            $arr = [$o, $o, $oDynamic, $oDynamic, &$oDynamic];

            return [$arr, sprintf(
                <<<'EOD'
                    list<Atk4\Core\QuietObjectWrapper|stdClass> [
                        %s {
                            'obj': %1$s *recursion*
                        },
                        %1$s *deduplicated*,
                        %s {
                            'bar': &0 %2$s *recursion*,
                            'foo': false
                        },
                        %2$s *deduplicated*,
                        &0 %2$s *deduplicated*
                    ]
                    EOD,
                QuietObjectWrapper::class . '#' . spl_object_id($o),
                \stdClass::class . '#' . spl_object_id($oDynamic),
            )];
        }];

        yield [static function () {
            $dt = new \DateTime();
            $ref = \WeakReference::create($dt);

            return [[$dt, $ref], sprintf(
                <<<'EOD'
                    list<DateTime|WeakReference> [
                        %s {},
                        WeakReference<%1$s>%s {}
                    ]
                    EOD,
                \DateTime::class . '#' . spl_object_id($dt),
                '#' . spl_object_id($ref),
            )];
        }];
        yield [static function () {
            $o = \WeakReference::create(new \DateTime());

            return [$o, 'WeakReference<*destroyed*>#' . spl_object_id($o) . ' {}'];
        }];

        yield [static fn () => ['foo ', '\'foo \''], 0];
        yield [static fn () => [null, 'null'], 0];
        yield [static fn () => [false, 'false'], 0];
        yield [static fn () => [fopen('php://memory', 'r+'), 'resource<stream>'], 0];
        yield [static fn () => [10, '10'], 0];
        yield [static fn () => [10.0, '10.0'], 0];
        yield [static fn () => [[], 'empty-array []'], -1];
        yield [static fn () => [[], 'empty-array []'], 0];
        yield [static fn () => [[], 'empty-array []'], 1];
        yield [static fn () => [['foo' => true], 'array<string, true> [...]'], 0];
        yield [static fn () => [['foo' => true], <<<'EOD'
            array<string, true> [
                'foo' => true
            ]
            EOD], 1];
        yield [static fn () => [['foo' => []], 'array<string, list> [...]'], 0];
        yield [static fn () => [['foo' => []], <<<'EOD'
            array<string, list> [
                'foo' => empty-array []
            ]
            EOD], 1];
        yield [static fn () => [['foo' => [true]], 'array<string, list> [...]'], 0];
        yield [static fn () => [['foo' => [true]], <<<'EOD'
            array<string, list> [
                'foo' => list<true> [...]
            ]
            EOD], 1];
        yield [static fn () => [['foo' => true, 'bar' => true], <<<'EOD'
            array<string, true> [...]
            EOD], 0];
        yield [static fn () => [['foo' => true, 'bar' => true], <<<'EOD'
            array<string, true> [
                'foo' => true,
                'bar' => true
            ]
            EOD], 1];
    }
}
