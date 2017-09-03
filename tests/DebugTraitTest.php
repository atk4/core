<?php

namespace atk4\core\tests;

use atk4\core\DebugTrait;

/**
 * @coversDefaultClass \atk4\core\DebugTrait
 */
class DebugTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test debug().
     */
    public function testDebug()
    {
        $m = new DebugMock();

        $this->assertEquals(false, $m->debug);

        $m->debug();
        $this->assertEquals(true, $m->debug);

        $m->debug(false);
        $this->assertEquals(false, $m->debug);

        $m->debug(true);
        $this->assertEquals(true, $m->debug);
    }

    public function testDebugOutput()
    {
        $this->expectOutputString("[atk4\\core\\tests\\DebugMock]: debug test1\n");

        $m = new DebugMock();
        $m->debug();

        $m->debug('debug test1');
    }

    public function testDebugNoOutput()
    {
        $this->expectOutputString('');

        $m = new DebugMock();

        $m->debug('debug test2');
    }

    public function testDebugApp()
    {
        $this->expectOutputString('');

        $app = new DebugAppMock();

        $m = new DebugMock();
        $m->app = $app;
        $m->debug();

        $m->debug('debug test2');

        $this->assertEquals(['debug', 'debug test2', []], $app->log);
    }
}

// @codingStandardsIgnoreStart
class DebugMock
{
    use DebugTrait;
}

class DebugAppMock implements \Psr\Log\LoggerInterface
{
    use \Psr\Log\LoggerTrait;

    public $log;

    public function log($level, $message, array $context = [])
    {
        $this->log = [$level, $message, $context];
    }
}
// @codingStandardsIgnoreEnd
