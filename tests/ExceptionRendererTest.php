<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\ExceptionRenderer\RendererAbstract;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class ExceptionRendererTest extends TestCase
{
    protected function createExceptionWithConstantTrace(): Exception
    {
        $setExceptionPropertyFx = static function (\Exception $e, string $k, $v) {
            $propRefl = new \ReflectionProperty(\Exception::class, $k);
            if (\PHP_VERSION_ID < 8_01_00) {
                $propRefl->setAccessible(true);
            }
            $propRefl->setValue($e, $v);
        };

        // based on https://3v4l.org/PqPXM
        $e = new Exception('My exception for <a> tag', 5);
        $e->addMoreInfo('foo', 111);
        $e->addSolution('Use <b> tag');
        $setExceptionPropertyFx($e, 'file', '/a/ex.php');
        $setExceptionPropertyFx($e, 'line', 10);
        $setExceptionPropertyFx($e, 'trace', [
            ['file' => '/a/main.php', 'line' => 20, 'function' => 'main', 'class' => self::class, 'type' => '::', 'args' => []],
        ]);

        $ePrevious = new \ClosedGeneratorException('kůň');
        $setExceptionPropertyFx($ePrevious, 'file', '/a/gen.php');
        $setExceptionPropertyFx($ePrevious, 'line', 1556677);
        $setExceptionPropertyFx($ePrevious, 'trace', [
            ['file' => '/a/text.php', 'line' => 12345, 'function' => 'formatValue', 'class' => self::class, 'object' => $this, 'type' => '->', 'args' => ['xxx']],
            ...$e->getTrace(),
        ]);
        $setExceptionPropertyFx($e, 'previous', $ePrevious);

        return $e;
    }

    public function testFormatHtml(): void
    {
        $e = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith('<', $e->getHtml());
        self::assertStringEndsWith(">\n", $e->getHtml());

        self::assertSame(<<<'EOF'
            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">Critical Error</div>
                    Atk4\Core\Exception [code: 5]:
                    My exception for &lt;a&gt; tag
                </div>
            </div>

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted red table">Exception Parameters</th></tr></thead>
                <tbody>
                    <tr><td><b>foo</b></td><td style="width: 100%;"><span style="white-space: pre-wrap;">111</span></td></tr>
                </tbody>
            </table>

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted green table">Suggested solutions</th></tr></thead>
                <tbody>
                    <tr><td>Use &lt;b&gt; tag</td></tr>
                </tbody>
            </table>

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="4">Stack Trace</th></tr></thead>
                <thead><tr><th style="text-align: right">#</th><th>File</th><th>Object</th><th>Method</th></tr></thead>
                <tbody>

                    <tr class="negative">
                        <td style="text-align: right"></td>
                        <td>/a/ex.php:10</td>
                        <td>-</td>
                        <td></td>
                    </tr>

                    <tr class="">
                        <td style="text-align: right">1</td>
                        <td>/a/main.php:20</td>
                        <td>-</td>
                        <td>main()</td>
                    </tr>

                </tbody>
            </table>

            <div class="ui top attached segment">
                <div class="ui top attached label">Caused by Previous Exception:</div>
            </div>

            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">Critical Error</div>
                    ClosedGeneratorException:
                    kůň
                </div>
            </div>

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="4">Stack Trace</th></tr></thead>
                <thead><tr><th style="text-align: right">#</th><th>File</th><th>Object</th><th>Method</th></tr></thead>
                <tbody>

                    <tr class="negative">
                        <td style="text-align: right"></td>
                        <td>/a/gen.php:1556677</td>
                        <td>-</td>
                        <td></td>
                    </tr>

                    <tr class="">
                        <td style="text-align: right">2</td>
                        <td>/a/text.php:12345</td>
                        <td>Atk4\Core\Tests\ExceptionRendererTest</td>
                        <td>formatValue(...)</td>
                    </tr>

                    <tr>
                        <td style="text-align: right">...</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                </tbody>
            </table>

            EOF, $e->getHtml());
    }

    public function testFormatJson(): void
    {
        $e = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith('{', $e->getJson());
        self::assertStringEndsWith('}', $e->getJson());

        self::assertSame(<<<'EOF'
            {
                "message": "My exception for <a> tag",
                "title": "Critical Error",
                "class": "Atk4\\Core\\Exception",
                "code": 5,
                "params": {
                    "foo": "111"
                },
                "solution": [
                    "Use <b> tag"
                ],
                "trace": [
                    {
                        "file": "/a/ex.php",
                        "line": 10,
                        "class": null,
                        "object": null,
                        "function": null,
                        "args": []
                    },
                    {
                        "file": "/a/main.php",
                        "line": 20,
                        "class": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                        "object": null,
                        "function": "main",
                        "args": []
                    }
                ],
                "previous": {
                    "message": "kůň",
                    "title": "Critical Error",
                    "class": "ClosedGeneratorException",
                    "code": 0,
                    "params": [],
                    "solution": [],
                    "trace": [
                        {
                            "file": "/a/gen.php",
                            "line": 1556677,
                            "class": null,
                            "object": null,
                            "function": null,
                            "args": []
                        },
                        {
                            "file": "/a/text.php",
                            "line": 12345,
                            "class": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                            "object": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                            "function": "formatValue",
                            "args": [
                                "xxx"
                            ]
                        },
                        {
                            "file": "/a/main.php",
                            "line": 20,
                            "class": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                            "object": null,
                            "function": "main",
                            "args": []
                        }
                    ],
                    "previous": null
                }
            }
            EOF, $e->getJson());
    }

    public function testFormatConsole(): void
    {
        $e = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith("\e[0;", $e->getColorfulText());
        self::assertStringEndsWith("\n", $e->getColorfulText());

        $expectedText = <<<"EOF"
            \e[0;1;41m--[ Critical Error ]\e[0m
            Atk4\\Core\\Exception: \e[1;30mMy exception for <a> tag\e[0m \e[31m[code: 5]\e[0m
            \e[91m                foo: 111\e[0m
            \e[92mSolution: Use <b> tag\e[0m
            \e[1;41m--[ Stack Trace ]\e[0m
                                           /a/ex.php:\e[31m  10\e[0m
                                         /a/main.php:\e[31m  20\e[0m  \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest::\e[0;33mmain\e[0;33m()\e[0m

            \e[1;45mCaused by Previous Exception:\e[0m
            \e[0;1;41m--[ Critical Error ]\e[0m
            ClosedGeneratorException: \e[1;30mkůň\e[0m
            \e[1;41m--[ Stack Trace ]\e[0m
                                          /a/gen.php:\e[31m1556677\e[0m
                                         /a/text.php:\e[31m12345\e[0m  - \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest\e[0m \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest::\e[0;33mformatValue\e[0;33m(...)\e[0m
                                                 ...

            EOF;
        self::assertSame(str_replace("\e", '\e', $expectedText), str_replace("\e", '\e', $e->getColorfulText()));
        self::assertSame($expectedText, $e->getColorfulText());

        self::assertStringNotContainsString('\e[', $e->getColorfulText());

        $e->setMessage("prevent\eESC");
        self::assertStringNotContainsString("prevent\e", $e->getColorfulText());
        self::assertStringNotContainsString("\eESC", $e->getColorfulText());
        self::assertStringContainsString('preventESC', $e->getColorfulText());
    }

    public function testToSafeString(): void
    {
        self::assertSame('1', RendererAbstract::toSafeString(1));
        self::assertSame('\'abc\'', RendererAbstract::toSafeString('abc'));

        self::assertSame(\stdClass::class, RendererAbstract::toSafeString(new \stdClass()));
        self::assertSame(\DateTime::class, RendererAbstract::toSafeString(new \DateTime()));
        self::assertSame(\Closure::class, RendererAbstract::toSafeString(static fn () => true));

        self::assertStringStartsWith('class@anonymous ', RendererAbstract::toSafeString(new class {}));
        self::assertStringStartsWith('ArrayIterator@anonymous ', RendererAbstract::toSafeString(new class([]) extends \ArrayIterator {}));
        self::assertMatchesRegularExpression('~^class@anonymous .+:\d+$~', RendererAbstract::toSafeString(new class {}));

        $resource = opendir(__DIR__);
        self::assertSame('resource (stream)', RendererAbstract::toSafeString($resource));
        closedir($resource);
        self::assertSame('resource (closed)', RendererAbstract::toSafeString($resource));

        $a = new TrackableMock();
        $a->shortName = 'foo';
        self::assertSame(TrackableMock::class . ' (foo)', RendererAbstract::toSafeString($a));

        $a = new TrackableMock();
        self::assertSame(TrackableMock::class . ' ()', RendererAbstract::toSafeString($a));

        $a = new TrackableMock2();
        $a->shortName = 'foo';
        self::assertSame(TrackableMock2::class . ' (foo)', RendererAbstract::toSafeString($a));

        $a = new TrackableMock2();
        $a->name = 'foo';
        self::assertSame(TrackableMock2::class . ' (foo)', RendererAbstract::toSafeString($a));
    }

    public function testExceptionFallback(): void
    {
        $e = new ExceptionThrowError('test / 👍', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionThrowError::class . '(2): test / 👍 !!';
        self::assertSame($expectedFallbackText, $e->getHtml());
        self::assertSame($expectedFallbackText, $e->getColorfulText());
        self::assertSame(
            json_encode(
                [
                    'message' => 'ATK4 CORE ERROR - EXCEPTION JSON RENDER FAILED: test / 👍',
                    'title' => ExceptionThrowError::class,
                    'class' => ExceptionThrowError::class,
                    'code' => 2,
                    'params' => [],
                    'solution' => [],
                    'trace' => [],
                    'previous' => [
                        'message' => 'Break __string()',
                        'title' => 'Exception',
                        'class' => 'Exception',
                        'code' => 0,
                    ],
                ],
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
            ),
            $e->getJson()
        );
    }
}

class TrackableMock2
{
    use NameTrait;
    use TrackableTrait;
}

class ExceptionThrowError extends Exception
{
    #[\Override]
    public function getCustomExceptionTitle(): string
    {
        throw new \Exception('Break __string()');
    }
}
