<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\ExceptionRenderer\RendererAbstract;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class ExceptionTest extends TestCase
{
    public function testBasic(): void
    {
        $ex = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        self::assertSame(['a1' => 111, 'a2' => 222], $ex->getParams());

        $ex = new Exception('PreviousError');
        $ex = new Exception('TestIt', 123, $ex);
        $ex->addMoreInfo('a1', 222);
        $ex->addMoreInfo('a2', 333);

        self::assertSame(['a1' => 222, 'a2' => 333], $ex->getParams());

        $ret = $ex->getHtml();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);

        $ret = $ex->getColorfulText();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);

        $ret = $ex->getJson();
        self::assertMatchesRegularExpression('~TestIt~', $ret);
        self::assertMatchesRegularExpression('~PreviousError~', $ret);
        self::assertMatchesRegularExpression('~333~', $ret);
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

    public function testMore(): void
    {
        $ex = new \Exception('Classic Exception');

        $ex = new Exception('atk4 exception', 0, $ex);
        $ex->setMessage('bumbum');

        $ret = $ex->getHtml();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $ex->getColorfulText();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $ex->getJson();
        self::assertMatchesRegularExpression('~Classic~', $ret);
        self::assertMatchesRegularExpression('~bumbum~', $ret);
    }

    public function testSolution(): void
    {
        $ex = new Exception('Exception with solution');
        $ex->addSolution('One Solution');

        $ret = $ex->getHtml();
        self::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $ex->getColorfulText();
        self::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $ex->getJson();
        self::assertMatchesRegularExpression('~One Solution~', $ret);
    }

    public function testSolution2(): void
    {
        $ex = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $ex->getColorfulText();
        self::assertMatchesRegularExpression('~1st Solution~', $ret);

        $ex = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $ex->getColorfulText();
        self::assertMatchesRegularExpression('~1st Solution~', $ret);
        self::assertMatchesRegularExpression('~2nd Solution~', $ret);
    }

    public function testPhpunitSelfDescribing(): void
    {
        $ex = (new Exception('My exception', 0))
            ->addMoreInfo('x', 'foo')
            ->addMoreInfo('y', ['bar' => 2.4, [], [[1]]]);

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

                EOF,
            $ex->toString()
        );
    }

    public function testExceptionFallback(): void
    {
        $ex = new ExceptionTestThrowError('test', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionTestThrowError::class . '(2): test !!';
        self::assertSame($expectedFallbackText, $ex->getHtml());
        self::assertSame($expectedFallbackText, $ex->getColorfulText());
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
            $ex->getJson()
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
    #[\Override]
    public function getCustomExceptionTitle(): string
    {
        throw new \Exception('just to cover __string');
    }
}
