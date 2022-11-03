<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\NameTrait;
use Atk4\Core\ExceptionRenderer\RendererAbstract;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class ExceptionTest extends TestCase
{
    public function testBasic(): void
    {
        $m = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        static::assertSame(['a1' => 111, 'a2' => 222], $m->getParams());

        $m = new Exception('PrevError');
        $m = new Exception('TestIt', 123, $m);
        $m->addMoreInfo('a1', 222);
        $m->addMoreInfo('a2', 333);

        static::assertSame(['a1' => 222, 'a2' => 333], $m->getParams());

        // get HTML
        $ret = $m->getHtml();
        static::assertMatchesRegularExpression('~TestIt~', $ret);
        static::assertMatchesRegularExpression('~PrevError~', $ret);
        static::assertMatchesRegularExpression('~333~', $ret);

        // get colorful text
        $ret = $m->getColorfulText();
        static::assertMatchesRegularExpression('~TestIt~', $ret);
        static::assertMatchesRegularExpression('~PrevError~', $ret);
        static::assertMatchesRegularExpression('~333~', $ret);

        // get JSON
        $ret = $m->getJson();
        static::assertMatchesRegularExpression('~TestIt~', $ret);
        static::assertMatchesRegularExpression('~PrevError~', $ret);
        static::assertMatchesRegularExpression('~333~', $ret);

        // to safe string
        $ret = RendererAbstract::toSafeString(1);
        static::assertSame('1', $ret);

        $ret = RendererAbstract::toSafeString('abc');
        static::assertSame('\'abc\'', $ret);

        $ret = RendererAbstract::toSafeString(new \stdClass());
        static::assertSame('stdClass', $ret);

        $a = new TrackableMock();
        $a->shortName = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        static::assertSame(TrackableMock::class . ' (foo)', $ret);

        $a = new TrackableMock2();
        $a->shortName = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        static::assertSame(TrackableMock2::class . ' (foo)', $ret);

        $a = new TrackableMock2();
        $a->name = 'foo';
        $ret = RendererAbstract::toSafeString($a);
        static::assertSame(TrackableMock2::class . ' (foo)', $ret);
    }

    public function testMore(): void
    {
        $m = new \Exception('Classic Exception');

        $m = new Exception('atk4 exception', 0, $m);
        $m->setMessage('bumbum');

        $ret = $m->getHtml();
        static::assertMatchesRegularExpression('~Classic~', $ret);
        static::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $m->getColorfulText();
        static::assertMatchesRegularExpression('~Classic~', $ret);
        static::assertMatchesRegularExpression('~bumbum~', $ret);

        $ret = $m->getJson();
        static::assertMatchesRegularExpression('~Classic~', $ret);
        static::assertMatchesRegularExpression('~bumbum~', $ret);
    }

    public function testSolution(): void
    {
        $m = new Exception('Exception with solution');
        $m->addSolution('One Solution');

        $ret = $m->getHtml();
        static::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $m->getColorfulText();
        static::assertMatchesRegularExpression('~One Solution~', $ret);

        $ret = $m->getJson();
        static::assertMatchesRegularExpression('~One Solution~', $ret);
    }

    public function testSolution2(): void
    {
        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $m->getColorfulText();
        static::assertMatchesRegularExpression('~1st Solution~', $ret);

        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $m->getColorfulText();
        static::assertMatchesRegularExpression('~1st Solution~', $ret);
        static::assertMatchesRegularExpression('~2nd Solution~', $ret);
    }

    public function testExceptionFallback(): void
    {
        $m = new ExceptionTestThrowError('test', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionTestThrowError::class . '(2): test !!';
        static::assertSame($expectedFallbackText, $m->getHtml());
        static::assertSame($expectedFallbackText, $m->getColorfulText());
        static::assertSame(
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
