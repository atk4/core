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
        $ex = new Exception('My exception for <a> tag');
        $ex->addMoreInfo('foo', 111);
        $ex->addSolution('Use <b> tag');

        foreach ([
            'file' => '/a/ex.php',
            'line' => 10,
            'trace' => [
                ['file' => '/a/text.php', 'line' => 12345, 'function' => 'formatValue', 'class' => self::class, 'object' => $this, 'type' => '->', 'args' => ['xxx']],
                ['file' => '/a/main.php', 'line' => 20, 'function' => 'main', 'class' => self::class, 'type' => '::', 'args' => []],
            ],
        ] as $k => $v) {
            $propRefl = new \ReflectionProperty(\Exception::class, $k);
            $propRefl->setAccessible(true);
            $propRefl->setValue($ex, $v);
        }

        return $ex;
    }

    public function testFormatHtml(): void
    {
        $ex = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith('<', $ex->getHtml());
        self::assertStringEndsWith(">\n", $ex->getHtml());

        self::assertSame(<<<'EOF'
            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">Critical Error</div>
                    Atk4\Core\Exception:
                    My exception for <a> tag
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
                <td></td>
                <td></td>
            </tr>

            <tr class="">
                <td style="text-align: right">2</td>
                <td>/a/text.php:12345</td>
                <td>Atk4\Core\Tests\ExceptionRendererTest</td>
                <td>formatValue(...)</td>
            </tr>

            <tr class="">
                <td style="text-align: right">1</td>
                <td>/a/main.php:20</td>
                <td></td>
                <td>main()</td>
            </tr>

                </tbody>
            </table>

            EOF, $ex->getHtml());
    }

    public function testFormatJson(): void
    {
        $ex = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith('{', $ex->getJson());
        self::assertStringEndsWith('}', $ex->getJson());

        self::assertSame(<<<'EOF'
            {
                "success": false,
                "code": 0,
                "message": "My exception for <a> tag",
                "title": "Critical Error",
                "class": "Atk4\\Core\\Exception",
                "params": {
                    "foo": "111"
                },
                "solution": [
                    "Use <b> tag"
                ],
                "trace": [
                    {
                        "line": 10,
                        "file": "/a/ex.php",
                        "class": null,
                        "object": null,
                        "function": null,
                        "args": []
                    },
                    {
                        "line": 12345,
                        "file": "/a/text.php",
                        "class": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                        "object": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                        "function": "formatValue",
                        "args": [
                            "xxx"
                        ]
                    },
                    {
                        "line": 20,
                        "file": "/a/main.php",
                        "class": "Atk4\\Core\\Tests\\ExceptionRendererTest",
                        "object": null,
                        "function": "main",
                        "args": []
                    }
                ],
                "previous": []
            }
            EOF, $ex->getJson());
    }

    public function testFormatConsole(): void
    {
        $ex = $this->createExceptionWithConstantTrace();

        self::assertStringStartsWith("\e[0;", $ex->getColorfulText());
        self::assertStringEndsWith("\n", $ex->getColorfulText());
        self::assertStringNotContainsString('\e[', $ex->getColorfulText());

        self::assertSame(str_replace("\e", '\e', <<<"EOF"
            \e[0;1;41m--[ Critical Error ]\e[0m
            Atk4\\Core\\Exception: \e[1;30mMy exception for <a> tag\e[0m
            \e[91m                foo: 111\e[0m
            \e[92mSolution: Use <b> tag\e[0m
            \e[1;41m--[ Stack Trace ]\e[0m
                                           /a/ex.php:\e[31m  10\e[0m
                                         /a/text.php:\e[31m12345\e[0m  - \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest\e[0m \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest::\e[0;33mformatValue\e[0;33m(...)\e[0m
                                         /a/main.php:\e[31m  20\e[0m  \e[32mAtk4\\Core\\Tests\\ExceptionRendererTest::\e[0;33mmain\e[0;33m()\e[0m

            EOF), str_replace("\e", '\e', $ex->getColorfulText()));
    }

    public function testToSafeString(): void
    {
        self::assertSame('1', RendererAbstract::toSafeString(1));

        self::assertSame('\'abc\'', RendererAbstract::toSafeString('abc'));

        self::assertSame(\stdClass::class, RendererAbstract::toSafeString(new \stdClass()));

        self::assertSame(\DateTime::class, RendererAbstract::toSafeString(new \DateTime()));

        self::assertSame(\Closure::class, RendererAbstract::toSafeString(static fn () => true));

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
        $ex = new ExceptionThrowError('test', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionThrowError::class . '(2): test !!';
        self::assertSame($expectedFallbackText, $ex->getHtml());
        self::assertSame($expectedFallbackText, $ex->getColorfulText());
        self::assertSame(
            json_encode(
                [
                    'success' => false,
                    'code' => 2,
                    'message' => 'Error during json renderer: test',
                    'title' => ExceptionThrowError::class,
                    'class' => ExceptionThrowError::class,
                    'params' => [],
                    'solution' => [],
                    'trace' => [],
                    'previous' => [
                        'title' => 'Exception',
                        'class' => 'Exception',
                        'code' => 0,
                        'message' => 'just to cover __string',
                    ],
                ],
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE
            ),
            $ex->getJson()
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
        throw new \Exception('just to cover __string');
    }
}
