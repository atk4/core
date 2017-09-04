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

    public function testLog1()
    {
        $this->expectOutputString("debug test3\n");

        $m = new DebugMock();
        $m->log('warning', 'debug test3');
    }

    public function testLog2()
    {
        $this->expectOutputString('');

        $app = new DebugAppMock();

        $m = new DebugMock();
        $m->app = $app;
        $m->log('warning', 'debug test3');

        $this->assertEquals(['warning', 'debug test3', []], $app->log);
    }

    public function testMessage1()
    {
        $this->expectOutputString("Could not notify user about: hello user\n");

        $m = new DebugMock();
        $m->userMessage('hello user');
    }

    public function testMessage2()
    {
        $this->expectOutputString('');
        $app = new DebugAppMock();

        $m = new DebugMock();
        $m->app = $app;
        $m->userMessage('hello user');

        $this->assertEquals(['warning', 'Could not notify user about: hello user', []], $app->log);
    }

    public function testMessage3()
    {
        $this->expectOutputString('');
        $app = new DebugAppMock2();

        $m = new DebugMock();
        $m->app = $app;
        $m->userMessage('hello user');

        $this->assertEquals(['hello user', []], $app->message);
    }

    protected function triggerDebugTraceChange($o, $label)
    {
        $o->debugTraceChange($label);
    }

    public function testTraceChange()
    {
        $app = new DebugAppMock();

        $m = new DebugMock();
        $m->app = $app;

        $this->triggerDebugTraceChange($m, 'test1'); // difference is 1 line between calls
        $this->triggerDebugTraceChange($m, 'test1');

        $pattern = '/Call path for .* has diverged \(was (.*):(.*), now (.*):(.*)\)/';
        $matches = [];
        preg_match($pattern, $app->log[1], $matches);

        // Changes detected
        $this->assertTrue(is_array($matches));
        $this->assertEquals(5, count($matches));
        $this->assertEquals($matches[1], $matches[3]);
        $this->assertEquals($matches[2] + 1, $matches[4]);

        $app->log = null;

        for ($i = 1; $i < 5; $i++) {
            $this->triggerDebugTraceChange($m, 'test2'); // called from same line all 5 times = no difference
        }

        // No changes in the trace change detected
        $this->assertNull($app->log);
    }
}

// @codingStandardsIgnoreStart
class DebugMock
{
    use DebugTrait {
        _echo_stderr as __echo_stderr;
    }

    protected function _echo_stderr($message)
    {
        echo $message;
    }
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

class DebugAppMock2 implements \atk4\core\AppUserNotificationInterface
{
    public $message;

    public function userNotification($message, array $context = [])
    {
        $this->message = [$message, $context];
    }
}

// @codingStandardsIgnoreEnd
