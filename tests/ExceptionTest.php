<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\ExceptionRenderer\RendererAbstract;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;
use PHPUnit\Event\Code\ThrowableBuilder;

class ExceptionTest extends TestCase
{
    public function testBasic(): void
    {
        $m = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        self::assertSame(['a1' => 111, 'a2' => 222], $m->getParams());

        $m = new Exception('PreviousError');
        $m = new Exception('TestIt', 123, $m);
        $m->addMoreInfo('a1', 222);
        $m->addMoreInfo('a2', 333);

        self::assertSame(['a1' => 222, 'a2' => 333], $m->getParams());

        // get HTML
        $ret = $m->getHtml();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);

        // get colorful text
        $ret = $m->getColorfulText();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);

        // get JSON
        $ret = $m->getJson();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);

        // to safe string
        $ret = RendererAbstract::toSafeString(1);
        self::assertSame('1', $ret);

        $ret = RendererAbstract::toSafeString('abc');
        self::assertSame('\'abc\'', $ret);

        $ret = RendererAbstract::toSafeString(new \stdClass());
        self::assertSame('stdClass', $ret);

        $a = new TrackableMock();
        $a->shortName = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        self::assertSame(TrackableMock::class . ' (foo)', $ret);

        $a = new TrackableMock2();
        $a->shortName = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        self::assertSame(TrackableMock2::class . ' (foo)', $ret);

        $a = new TrackableMock2();
        $a->name = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        self::assertSame(TrackableMock2::class . ' (foo)', $ret);
    }

    public function testMore(): void
    {
        $m = new \Exception('Classic Exception');

        $m = new Exception('atk4 exception', 0, $m);
        $m->setMessage('bumbum');

        $ret = $m->getHtml();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $m->getColorfulText();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $m->getJson();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);
    }

    public function testSolution(): void
    {
        $m = new Exception('Exception with solution');
        $m->addSolution('One Solution');

        $ret = $m->getHtml();
        self::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $m->getColorfulText();
        self::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $m->getJson();
        self::assertMatchesRegularExpression('~One Solution~', $ret);
    }

    public function testSolution2(): void
    {
        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $m->getColorfulText();
        self::assertMatchesRegularExpression('~1st Solution~', $ret);

        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $m->getColorfulText();
        self::assertMatchesRegularExpression('~1st Solution~', $ret);
        self::assertMatchesRegularExpression('~2nd Solution~', $ret);
    }

    public function testPhpunitSelfDescribing(): void
    {
        $innerLine = __LINE__ + 1;
        $innerException = new \Error('Inner Exception');
        $line = __LINE__ + 1;
        $line -= 2; // https://github.com/sebastianbergmann/phpunit/issues/5574
        $exception = (new Exception('My exception', 0, $innerException))
            ->addMoreInfo('x', 'foo')
            ->addMoreInfo('y', ['bar' => 2.4, [], [[1]]]);

        $phpunitThrowable = ThrowableBuilder::from($exception);
        $phpunitThrowableStr = str_replace(\PHP_EOL, "\n", $phpunitThrowable->asString());

        self::assertSame(
            str_replace('[lineMain]', (string) $line, str_replace('[lineInner]', (string) $innerLine, <<<'EOF'
                Atk4\Core\Exception: My exception
                  x: 'foo'
                  y: [
                      'bar': 2.4,
                      0: [],
                      1: [
                          ...
                        ]
                    ]

                self.php:[lineMain]

                Caused by
                Error: Inner Exception

                self.php:[lineInner]
                EOF)) . "\n", // NL in the string is not parsed by Netbeans, see https://github.com/apache/netbeans/issues/4345
            str_replace(__FILE__, 'self.php', $phpunitThrowableStr)
        );
    }

    public function testExceptionFallback(): void
    {
        $m = new ExceptionTestThrowError('test', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionTestThrowError::class . '(2): test !!';
        self::assertSame($expectedFallbackText, $m->getHtml());
        self::assertSame($expectedFallbackText, $m->getColorfulText());
        self::assertSame(
            json_encode(
                [
                    'success' => false,
                    'code' => 2,
                    'message' => 'Error during json renderer: test',
                    'title' => ExceptionTestThrowError::class,
                    'class' => ExceptionTestThrowError::class,
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
            $m->getJson()
        );
    }
}

class TrackableMock2
{
    use NameTrait;
    use TrackableTrait;
}

class ExceptionTestThrowError extends Exception
{
    public function getCustomExceptionTitle(): string
    {
        throw new \Exception('just to cover __string');
    }
}
