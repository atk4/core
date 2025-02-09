<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;

class ExceptionTest extends TestCase
{
    public function testBasic(): void
    {
        $ex = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        self::assertSame(['a1' => 111, 'a2' => 222], $ex->getParams());

        $ex = new Exception('TestIt', 123, new Exception('PreviousError'));
        $ex->addMoreInfo('a1', 222);
        $ex->addMoreInfo('a2', 333);

        self::assertSame(['a1' => 222, 'a2' => 333], $ex->getParams());

        $ret = $ex->getHtml();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);

        $ret = $ex->getColorfulText();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);

        $ret = $ex->getJson();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);
    }

    public function testMore(): void
    {
        $ex = new \Exception('Classic Exception');

        $ex = new Exception('atk4 exception', 0, $ex);
        $ex->setMessage('bumbum');

        $ret = $ex->getHtml();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);

        $ret = $ex->getColorfulText();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);

        $ret = $ex->getJson();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);
    }

    public function testSolution(): void
    {
        $ex = new Exception('Exception with solution');
        $ex->addSolution('One Solution');

        $ret = $ex->getHtml();
        self::assertStringContainsString('One Solution', $ret);

        $ret = $ex->getColorfulText();
        self::assertStringContainsString('One Solution', $ret);

        $ret = $ex->getJson();
        self::assertStringContainsString('One Solution', $ret);
    }

    public function testSolution2(): void
    {
        $ex = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $ex->getColorfulText();
        self::assertStringContainsString('1st Solution', $ret);

        $ex = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $ex->getColorfulText();
        self::assertStringContainsString('1st Solution', $ret);
        self::assertStringContainsString('2nd Solution', $ret);
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
}
