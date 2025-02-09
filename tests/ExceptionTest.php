<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;

class ExceptionTest extends TestCase
{
    public function testBasic(): void
    {
        $e = (new Exception('TestIt'))
            ->addMoreInfo('a1', 111)
            ->addMoreInfo('a2', 222);

        self::assertSame(['a1' => 111, 'a2' => 222], $e->getParams());

        $e = new Exception('TestIt', 123, new Exception('PreviousError'));
        $e->addMoreInfo('a1', 222);
        $e->addMoreInfo('a2', 333);

        self::assertSame(['a1' => 222, 'a2' => 333], $e->getParams());

        $ret = $e->getHtml();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);

        $ret = $e->getColorfulText();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);

        $ret = $e->getJson();
        self::assertStringContainsString('TestIt', $ret);
        self::assertStringContainsString('PreviousError', $ret);
        self::assertStringContainsString('333', $ret);
    }

    public function testMore(): void
    {
        $e = new \Exception('Classic Exception');

        $e = new Exception('atk4 exception', 0, $e);
        $e->setMessage('bumbum');

        $ret = $e->getHtml();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);

        $ret = $e->getColorfulText();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);

        $ret = $e->getJson();
        self::assertStringContainsString('Classic', $ret);
        self::assertStringContainsString('bumbum', $ret);
    }

    public function testSolution(): void
    {
        $e = new Exception('Exception with solution');
        $e->addSolution('One Solution');

        $ret = $e->getHtml();
        self::assertStringContainsString('One Solution', $ret);

        $ret = $e->getColorfulText();
        self::assertStringContainsString('One Solution', $ret);

        $ret = $e->getJson();
        self::assertStringContainsString('One Solution', $ret);
    }

    public function testSolution2(): void
    {
        $e = (new Exception('Exception with solution'))
            ->addSolution('1st Solution');

        $ret = $e->getColorfulText();
        self::assertStringContainsString('1st Solution', $ret);

        $e = (new Exception('Exception with solution'))
            ->addSolution('1st Solution')
            ->addSolution('2nd Solution');

        $ret = $e->getColorfulText();
        self::assertStringContainsString('1st Solution', $ret);
        self::assertStringContainsString('2nd Solution', $ret);
    }

    public function testPhpunitSelfDescribing(): void
    {
        $e = (new Exception('My exception', 0))
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
            $e->toString()
        );
    }
}
