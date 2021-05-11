<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AtkPhpunit;
use Atk4\Core\Exception;
use Atk4\Core\TrackableTrait;

/**
 * @coversDefaultClass \Atk4\Core\Exception
 */
class ExceptionTest extends AtkPhpunit\TestCase
{
    public function testBasic(): void
    {
        $m = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        // params
        $this->assertSame(['a1' => 111, 'a2' => 222], $m->getParams());

        $m = new Exception('PrevError');
        $m = new Exception('TestIt', 123, $m);
        $m->addMoreInfo('a1', 222);
        $m->addMoreInfo('a2', 333);

        // params
        $this->assertSame(['a1' => 222, 'a2' => 333], $m->getParams());

        // get HTML
        $ret = $m->getHtml();
        $this->assertMatchesRegularExpression('/TestIt/', $ret);
        $this->assertMatchesRegularExpression('/PrevError/', $ret);
        $this->assertMatchesRegularExpression('/333/', $ret);

        // get colorful text
        $ret = $m->getColorfulText();
        $this->assertMatchesRegularExpression('/TestIt/', $ret);
        $this->assertMatchesRegularExpression('/PrevError/', $ret);
        $this->assertMatchesRegularExpression('/333/', $ret);

        // get JSON
        $ret = $m->getJson();
        $this->assertMatchesRegularExpression('/TestIt/', $ret);
        $this->assertMatchesRegularExpression('/PrevError/', $ret);
        $this->assertMatchesRegularExpression('/333/', $ret);

        // to string
        $ret = $m->toString(1);
        $this->assertSame('1', $ret);

        $ret = $m->toString('abc');
        $this->assertSame('\'abc\'', $ret);

        $ret = $m->toString(new \stdClass());
        $this->assertSame('stdClass', $ret);

        $a = new TrackableMock2();
        $a->name = 'foo';
        $ret = $m->toString($a);
        $this->assertSame(TrackableMock2::class . ' (foo)', $ret);
    }

    public function testMore(): void
    {
        $m = new \Exception('Classic Exception');

        $m = new Exception('atk4 exception', 0, $m);
        $m->setMessage('bumbum');

        $ret = $m->getHtml();
        $this->assertMatchesRegularExpression('/Classic/', $ret);
        $this->assertMatchesRegularExpression('/bumbum/', $ret);

        $ret = $m->getColorfulText();
        $this->assertMatchesRegularExpression('/Classic/', $ret);
        $this->assertMatchesRegularExpression('/bumbum/', $ret);

        $ret = $m->getJson();
        $this->assertMatchesRegularExpression('/Classic/', $ret);
        $this->assertMatchesRegularExpression('/bumbum/', $ret);
    }

    public function testSolution(): void
    {
        $m = new Exception('Exception with solution');
        $m->addSolution('One Solution');

        $ret = $m->getHtml();
        $this->assertMatchesRegularExpression('/One Solution/', $ret);

        $ret = $m->getColorfulText();
        $this->assertMatchesRegularExpression('/One Solution/', $ret);

        $ret = $m->getJson();
        $this->assertMatchesRegularExpression('/One Solution/', $ret);
    }

    public function testSolution2(): void
    {
        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $m->getColorfulText();
        $this->assertMatchesRegularExpression('/1st Solution/', $ret);

        $m = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $m->getColorfulText();
        $this->assertMatchesRegularExpression('/1st Solution/', $ret);
        $this->assertMatchesRegularExpression('/2nd Solution/', $ret);
    }

    public function testExceptionFallback(): void
    {
        $m = new ExceptionTestThrowError('test', 2);
        $expectedFallbackText = '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
            . ExceptionTestThrowError::class . '(2): test !!';
        $this->assertSame($expectedFallbackText, $m->getHtml());
        $this->assertSame($expectedFallbackText, $m->getColorfulText());
        $this->assertSame(
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
    use TrackableTrait;
}

class ExceptionTestThrowError extends Exception
{
    public function getCustomExceptionTitle(): string
    {
        throw new \Exception('just to cover __string');
    }
}
