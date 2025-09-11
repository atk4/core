<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DumpHelper;
use Atk4\Core\ExceptionRenderer\Html as HtmlExceptionRenderer;
use Atk4\Core\Phpunit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DumpHelperTest extends TestCase
{
    private static function relativizePath(string $path): string
    {
        return \Closure::bind(static function () use ($path) {
            return (new HtmlExceptionRenderer((new \ReflectionClass(\Exception::class))->newInstanceWithoutConstructor()))->tryRelativizePathsInString($path);
        }, null, HtmlExceptionRenderer::class)();
    }

    private static function getPropertyMangledPrivateName(string $class, string $name): string
    {
        return "\0" . $class . "\0" . $name;
    }

    private static function getPropertyMangledProtectedName(string $name): string
    {
        return "\0*\0" . $name;
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

        yield 'preserve order' => [new class extends \stdClass {
            /** @var string */
            public $bar = 'x';
            /** @var string */
            public $bar2 = 'y';
            /** @var string */
            public $bar1 = 'z';
        }, [
            'bar' => 'x',
            'bar2' => 'y',
            'bar1' => 'z',
        ]];

        $o = new class extends \stdClass {
            /** @var string */
            public $bar2 = 'x';
        };
        $o->foo = 1;
        $o->bar = null;
        yield 'dynamic property' => [$o, [
            'bar2' => 'x',
            'foo' => 1,
            'bar' => null,
        ]];

        yield 'private property' => [new DumpHelperPriPro('x', 'y'), [
            self::getPropertyMangledPrivateName(DumpHelperPriPro::class, 'pri') => 'x',
            self::getPropertyMangledProtectedName('pro') => 'y',
        ]];

        $o2 = new class('x', 'y') extends DumpHelperPriPro {
            protected string $pro;
            private bool $a; // @phpstan-ignore property.onlyWritten, property.tooWideBool
            protected bool $b;
            public bool $c;
            private bool $pri; // @phpstan-ignore property.onlyWritten, property.tooWideBool

            public function __construct(string $pri, string $pro)
            {
                parent::__construct($pri, $pro);

                $this->a = true;
                $this->pri = false;
                $this->pro = $pro;
            }
        };
        yield 'redeclared private property' => [$o2, [
            self::getPropertyMangledPrivateName(DumpHelperPriPro::class, 'pri') => 'x',
            self::getPropertyMangledProtectedName('pro') => 'y',
            self::getPropertyMangledPrivateName(get_class($o2), 'a') => true,
            self::getPropertyMangledProtectedName('b') => null,
            'c' => null,
            self::getPropertyMangledPrivateName(get_class($o2), 'pri') => false,
        ]];

        $o3 = new DumpHelperWithDebugInfo();
        $o3->foo = 10;
        yield '__debugInfo() implemented' => [$o3, [
            'x' => 10,
        ]];

        $o4 = new class extends DumpHelperWithDebugInfo {
            public int $bar = 20;
        };
        $o4->foo = 10;
        yield '__debugInfo() implemented in child class' => [$o4, [
            'x' => 10,
        ]];
    }

    /**
     * @param mixed $value
     */
    private static function getRid(&$value): string
    {
        return \ReflectionReference::fromArrayElement([&$value], 0)->getId();
    }

    /**
     * @dataProvider provideFindDuplicateOidsRidsCases
     *
     * @param \Closure(): array{mixed, array<int, positive-int>, array<string, positive-int>} $makeCaseFx
     * @param int<0, max>                                                                     $maxDepth
     */
    #[DataProvider('provideFindDuplicateOidsRidsCases')]
    public function testFindDuplicateOidsRids(\Closure $makeCaseFx, int $maxDepth = \PHP_INT_MAX): void
    {
        [$value, $expectedDuplicateOids, $expectedDuplicateRids] = $makeCaseFx();

        $dumpHelper = new DumpHelper();
        $duplicateOids = [];
        $duplicateRids = [];
        \Closure::bind(static function () use ($dumpHelper, $value, $maxDepth, &$duplicateOids, &$duplicateRids) {
            $dumpHelper->findDuplicateOidsRids($value, null, $maxDepth, $duplicateOids, $duplicateRids);
        }, null, DumpHelper::class)();

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

            return [$v, [], []];
        }];

        yield 'object' => [static function () {
            $o = new \stdClass();

            return [$o, [
                spl_object_id($o) => 1,
            ], []];
        }];

        yield 'DateTime' => [static function () {
            $o = new \DateTime('2013-02-20 20:00:12 UTC');

            return [$o, [
                spl_object_id($o) => 1,
            ], []];
        }];

        yield 'Closure' => [static function () {
            $fx = static fn () => true;

            return [$fx, [
                spl_object_id($fx) => 1,
            ], []];
        }];

        yield 'array with objects' => [static function () {
            $o = new \stdClass();
            $o2 = new \stdClass();
            $oCopy = $o;
            $arr = [&$o, &$o2, &$o, &$oCopy];

            return [$arr, [
                spl_object_id($o) => 3,
                spl_object_id($o2) => 1,
            ], [
                self::getRid($o) => 2,
            ]];
        }];

        yield 'array recursion' => [static function () {
            $v = false;
            $arr = [&$v];
            $arr[] = &$arr;
            $arr2 = [&$arr, &$v];

            return [$arr2, [], [
                self::getRid($arr) => 2,
                self::getRid($v) => 2,
            ]];
        }];

        yield 'array recursion top' => [static function () {
            $v = false;
            $arr = [&$v, &$v];
            $arr[] = &$arr;

            return [$arr, [], [
                self::getRid($v) => 4,
                self::getRid($arr) => 2,
            ]];
        }];

        yield 'object recursion' => [static function () {
            $o = new \stdClass();
            $o->foo = &$o;
            $oCopy = $o;

            $v = false;
            $oDynamic = new \stdClass();
            $oDynamic->foo = &$v;
            $oDynamic->bar = &$oDynamic;
            $oDynamicCopy = $oDynamic;

            $arr = [&$o, &$o, &$oDynamic, &$oDynamic, &$v, &$oCopy, &$oDynamicCopy];

            return [$arr, [
                spl_object_id($o) => 4,
                spl_object_id($oDynamic) => 4,
            ], [
                self::getRid($o) => 3,
                self::getRid($oDynamic) => 3,
                self::getRid($v) => 2,
            ]];
        }];

        yield 'array max depth 0' => [static function () {
            $o = new \stdClass();
            $oThrow = new DumpHelperWithDebugInfoThrow();
            $arr = [$o, [1], $oThrow];

            return [$arr, [], []];
        }, 0];

        yield 'array max depth -1' => [static function () {
            $o = new \stdClass();
            $oThrow = new DumpHelperWithDebugInfoThrow();
            $arr = [$o, [1], $oThrow];

            return [$arr, [], []];
        }, 0];

        yield 'array max depth 1' => [static function () {
            $o = new \stdClass();
            $o2 = new \stdClass();
            $oThrow = new DumpHelperWithDebugInfoThrow();
            $v = [&$o2, [1], $oThrow];
            $arr = [&$o, &$v, &$o, &$v, [&$o], [[&$o]], [&$o, &$o]];

            return [$arr, [
                spl_object_id($o) => 4,
            ], [
                self::getRid($o) => 4,
                self::getRid($v) => 2,
            ]];
        }, 1];
    }

    /**
     * @dataProvider providePrintReadableCases
     *
     * @param \Closure(): array{mixed, string} $makeCaseFx
     * @param int<0, max>                      $maxDepth
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

            return [$dt, sprintf(
                <<<'EOF'
                    %s {
                        'date': '2013-02-20 20:00:12.000000',
                        'timezone_type': 3,
                        'timezone': 'UTC'
                    }
                    EOF,
                \DateTime::class . '#' . spl_object_id($dt)
            )];
        }];
        yield [static function () {
            $fx = static fn () => true;

            return [$fx, \Closure::class . '#' . spl_object_id($fx) . ' {}'];
        }];
        yield 'dynamic property with special characters' => [static function () {
            $o = new \stdClass();
            $o->{"x\0y"} = 'a';
            $o->{"x\ny"} = 'b';
            $o->{'x-y'} = 'c';
            $o->{0} = 'd';
            $o->{'1.0'} = 'e';

            return [$o, sprintf(
                <<<'EOF'
                    %s {
                        'x%sy': 'a',
                        <<<'EOD'
                            x
                            y
                            EOD: 'b',
                        'x-y': 'c',
                        0: 'd',
                        '1.0': 'e'
                    }
                    EOF,
                'stdClass#' . spl_object_id($o),
                "\0"
            )];
        }];
        yield 'private property' => [static function () {
            $o = new DumpHelperPriPro('x', 'y');

            return [$o, sprintf(
                <<<'EOD'
                    %s {
                        'pri': 'x',
                        'pro': 'y'
                    }
                    EOD,
                DumpHelperPriPro::class . '#' . spl_object_id($o),
            )];
        }];
        yield 'private property in child class' => [static function () {
            $o = new class('x', 'y') extends DumpHelperPriPro {};

            return [$o, sprintf(
                <<<'EOD'
                    %s {
                        'pri': 'x',
                        'pro': 'y'
                    }
                    EOD,
                DumpHelperPriPro::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 9) . '#' . spl_object_id($o)
            )];
        }];
        yield 'redeclared private property' => [static function () {
            $o = new class('x', 'y') extends DumpHelperPriPro {
                protected string $pro;
                private bool $a; // @phpstan-ignore property.onlyWritten, property.tooWideBool
                protected bool $b = false;
                public bool $c = false;
                private bool $pri; // @phpstan-ignore property.onlyWritten, property.tooWideBool

                public function __construct(string $pri, string $pro)
                {
                    parent::__construct($pri, $pro);

                    $this->a = true;
                    $this->pri = false;
                    $this->pro = $pro;
                }
            };

            return [$o, sprintf(
                <<<'EOD'
                    %s#%d {
                        'pri:%s': 'x',
                        'pro': 'y',
                        'a': true,
                        'b': false,
                        'c': false,
                        'pri:%1$s': false
                    }
                    EOD,
                DumpHelperPriPro::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 28),
                spl_object_id($o),
                DumpHelperPriPro::class
            )];
        }];
        yield 'redeclared private property ii' => [static function () {
            $o = new class('x', 'y') extends DumpHelperPriPro {
                protected bool $pri;
                public string $pro;

                public function __construct(string $pri, string $pro)
                {
                    parent::__construct($pri, $pro);

                    $this->pri = false;
                    $this->pro = $pro;
                }
            };

            return [$o, sprintf(
                <<<'EOD'
                    %s#%d {
                        'pri:%s': 'x',
                        'pro': 'y',
                        'pri': false
                    }
                    EOD,
                DumpHelperPriPro::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 21),
                spl_object_id($o),
                DumpHelperPriPro::class
            )];
        }];
        yield 'redeclared private property iii' => [static function () {
            $o = new class('x', 'y') extends DumpHelperPriPro {
                public bool $pri;
                public string $pro;

                public function __construct(string $pri, string $pro)
                {
                    parent::__construct($pri, $pro);

                    $this->pri = false;
                    $this->pro = $pro;
                }
            };

            return [$o, sprintf(
                <<<'EOD'
                    %s#%d {
                        'pri:%s': 'x',
                        'pro': 'y',
                        'pri': false
                    }
                    EOD,
                DumpHelperPriPro::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 21),
                spl_object_id($o),
                DumpHelperPriPro::class
            )];
        }];
        yield 'uninitialized property' => [static function () {
            $o = (new \ReflectionClass(DumpHelperPriPro::class))->newInstanceWithoutConstructor();

            return [$o, sprintf(
                <<<'EOD'
                    %s {
                        'pri': *uninitialized*,
                        'pro': *uninitialized*
                    }
                    EOD,
                DumpHelperPriPro::class . '#' . spl_object_id($o)
            )];
        }];
        yield 'unset property' => [static function () {
            $o = new class {
                /** @var mixed */
                public $foo;
            };
            unset($o->{'foo'});

            $oTyped = new DumpHelperPriPro('x', 'y');
            \Closure::bind(static function () use ($oTyped) {
                unset($oTyped->{'pri'});
            }, null, DumpHelperPriPro::class)();

            $oDynamic = new \stdClass();
            $oDynamic->foo = 5;
            unset($oDynamic->{'foo'});

            return [[$o, $oTyped, $oDynamic], sprintf(
                <<<'EOD'
                    list<%s|%s|stdClass> [
                        %2$s#%d {
                            'foo': *unset*
                        },
                        %1$s#%d {
                            'pri': *uninitialized*,
                            'pro': 'y'
                        },
                        %s {}
                    ]
                    EOD,
                DumpHelperPriPro::class,
                'class@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 29),
                spl_object_id($o),
                spl_object_id($oTyped),
                \stdClass::class . '#' . spl_object_id($oDynamic)
            )];
        }];
        yield '__debugInfo() implemented' => [static function () {
            $o = new DumpHelperWithDebugInfo();
            $o->foo = $o;

            $o2 = new DumpHelperWithDebugInfo();
            $o2->foo = &$o;

            return [[$o, $o2], sprintf(
                <<<'EOD'
                    list<%s> [
                        %s {
                            'x': %2$s *recursion*
                        },
                        %s {
                            'x': %2$s *deduplicated*
                        }
                    ]
                    EOD,
                DumpHelperWithDebugInfo::class,
                DumpHelperWithDebugInfo::class . '#' . spl_object_id($o),
                DumpHelperWithDebugInfo::class . '#' . spl_object_id($o2)
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
            $o = new \stdClass();
            $o2 = new \stdClass();
            $o3 = new \stdClass();
            $o3->foo = $o2;

            return [[$o, $o, $o2, [$o], $o3, $o3], sprintf(
                <<<'EOD'
                    list<list|stdClass> [
                        %s {},
                        %1$s *deduplicated*,
                        %s {},
                        list<stdClass> [
                            %1$s *deduplicated*
                        ],
                        %s {
                            'foo': %2$s *deduplicated*
                        },
                        %3$s *deduplicated*
                    ]
                    EOD,
                \stdClass::class . '#' . spl_object_id($o),
                \stdClass::class . '#' . spl_object_id($o2),
                \stdClass::class . '#' . spl_object_id($o3),
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
                            'foo': list<true> [
                                &1 true
                            ],
                            'bar': &0 list<true> *deduplicated*,
                            'baz': list<list> [
                                &0 list<true> *deduplicated*
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
            $v = false;
            $arr = [&$v, &$v];
            $arr[] = &$arr;

            return [$arr, <<<'EOD'
                list<false|list> [
                    &0 false,
                    &0 false,
                    &1 list<false|list> [
                        &0 false,
                        &0 false,
                        &1 list<false|list> *recursion*
                    ]
                ]
                EOD];
        }];
        yield 'track object recursion' => [static function () {
            $o = new \stdClass();
            $o->obj = &$o;
            $o2 = new class extends \stdClass {
                public object $obj;
            };
            $o2->obj = &$o;
            $o2->dynamic = &$o;

            $arr = [$o, $o, &$o, $o2];

            return [$arr, sprintf(
                <<<'EOD'
                    list<stdClass|%s> [
                        %s {
                            'obj': &0 %2$s *recursion*
                        },
                        %2$s *deduplicated*,
                        &0 %2$s *deduplicated*,
                        %1$s#%s {
                            'obj': &0 %2$s *deduplicated*,
                            'dynamic': &0 %2$s *deduplicated*
                        }
                    ]
                    EOD,
                \stdClass::class . '@anonymous ' . self::relativizePath(__FILE__) . ':' . (__LINE__ - 22),
                \stdClass::class . '#' . spl_object_id($o),
                spl_object_id($o2)
            )];
        }];

        yield [static function () {
            $o = new \stdClass();
            $ref = \WeakReference::create($o);

            return [[$o, $ref], sprintf(
                <<<'EOD'
                    list<WeakReference|stdClass> [
                        %s {},
                        WeakReference<%1$s>%s {}
                    ]
                    EOD,
                \stdClass::class . '#' . spl_object_id($o),
                '#' . spl_object_id($ref),
            )];
        }];
        yield [static function () {
            $ref = \WeakReference::create(new \stdClass());

            return [$ref, 'WeakReference<*destroyed*>#' . spl_object_id($ref) . ' {}'];
        }];

        yield [static fn () => ['foo ', '\'foo \''], 0];
        yield [static fn () => [null, 'null'], 0];
        yield [static fn () => [false, 'false'], 0];
        yield [static fn () => [fopen('php://memory', 'r+'), 'resource<stream>'], 0];
        yield [static fn () => [10, '10'], 0];
        yield [static fn () => [10.0, '10.0'], 0];
        $arrArr = [[], 'foo' => [], 'bar' => [true], 'baz' => [[true, 100]]];
        yield [static fn () => [$arrArr, 'array<int|string, list> [...]'], -1];
        yield [static fn () => [$arrArr, 'array<int|string, list> [...]'], 0];
        yield [static fn () => [$arrArr, <<<'EOD'
            array<int|string, list> [
                0 => empty-array [],
                'foo' => empty-array [],
                'bar' => list<true> [
                    true
                ],
                'baz' => list<list> [
                    list<int|true> [...]
                ]
            ]
            EOD], 1];
        yield [static fn () => [$arrArr, <<<'EOD'
            array<int|string, list> [
                0 => empty-array [],
                'foo' => empty-array [],
                'bar' => list<true> [
                    true
                ],
                'baz' => list<list> [
                    list<int|true> [
                        true,
                        100
                    ]
                ]
            ]
            EOD], 2];
        yield [static fn () => [$arrArr, <<<'EOD'
            array<int|string, list> [
                0 => empty-array [],
                'foo' => empty-array [],
                'bar' => list<true> [
                    true
                ],
                'baz' => list<list> [
                    list<int|true> [
                        true,
                        100
                    ]
                ]
            ]
            EOD], 3];
        yield [static fn () => [[[]], <<<'EOD'
            list<list> [
                empty-array []
            ]
            EOD], \PHP_INT_MAX];
        $arrThrow = ['foo' => true, new DumpHelperWithDebugInfoThrow()];
        yield [static fn () => [$arrThrow, 'array<int|string, ' . DumpHelperWithDebugInfoThrow::class . '|true> [...]'], 0];
        $v = false;
        $arrRef = [&$v, [&$v, 100]];
        yield [static fn () => [$arrRef, 'list<false|list> [...]'], 0];
        yield [static fn () => [$arrRef, <<<'EOD'
            list<false|list> [
                & false,
                list<false|int> [...]
            ]
            EOD], 1];
        yield [static fn () => [$arrRef, <<<'EOD'
            list<false|list> [
                &0 false,
                list<false|int> [
                    &0 false,
                    100
                ]
            ]
            EOD], 2];
        $v2 = false;
        $arrRef2 = [&$v, [[&$v, &$v2]], &$v2, &$v2];
        yield [static fn () => [$arrRef2, <<<'EOD'
            list<false|list> [
                & false,
                list<list> [
                    list<false> [...]
                ],
                &0 false,
                &0 false
            ]
            EOD], 1];
        yield [static fn () => [$arrRef2, <<<'EOD'
            list<false|list> [
                &0 false,
                list<list> [
                    list<false> [
                        &0 false,
                        &1 false
                    ]
                ],
                &1 false,
                &1 false
            ]
            EOD], 2];
    }

    public function testPrintReadableExpandException(): void
    {
        $dumpHelper = new DumpHelper();
        $arrArrScalarThrow = ['foo' => [true], new DumpHelperWithDebugInfoThrow()];

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Depth limit must be honored');
        $dumpHelper->printReadable($arrArrScalarThrow, 1);
    }
}

class DumpHelperPriPro
{
    private string $pri; // @phpstan-ignore property.onlyWritten
    protected string $pro;

    public function __construct(string $pri, string $pro)
    {
        $this->pri = $pri;
        $this->pro = $pro;
    }
}

class DumpHelperWithDebugInfo
{
    /** @var mixed */
    public $foo;

    /**
     * @return array{x: mixed}
     */
    public function __debugInfo(): array
    {
        return ['x' => $this->foo];
    }
}

class DumpHelperWithDebugInfoThrow
{
    /**
     * @return never
     */
    public function __debugInfo(): array
    {
        throw new \Error('Depth limit must be honored');
    }
}
