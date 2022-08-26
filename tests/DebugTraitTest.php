<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\DebugTrait;
use Atk4\Core\Phpunit\TestCase;

class DebugTraitTest extends TestCase
{
    public function testDebug(): void
    {
        $m = new DebugMock();

        static::assertFalse($m->debug);

        $m->debug();
        static::assertTrue($m->debug);

        $m->debug(false);
        static::assertFalse($m->debug);

        $m->debug(true);
        static::assertTrue($m->debug);
    }

    public function testDebugOutput(): void
    {
        $this->expectOutputString("[Atk4\\Core\\Tests\\DebugMock]: debug test1\n");

        $m = new DebugMock();
        $m->debug();

        $m->debug('debug test1');
    }

    public function testDebugNoOutput(): void
    {
        $this->expectOutputString('');

        $m = new DebugMock();

        $m->debug('debug test2');
    }

    public function testDebugApp(): void
    {
        $this->expectOutputString('');

        $app = new DebugAppMock();
        $app->logger = $app;

        $m = new DebugMock();
        $m->setApp($app);
        $m->debug();

        $m->debug('debug test2');

        static::assertSame(['debug', 'debug test2', []], $app->log);
    }

    public function testLog1(): void
    {
        $this->expectOutputString("debug test3\n");

        $m = new DebugMock();
        $m->log('warning', 'debug test3');
    }

    public function testLog2(): void
    {
        $this->expectOutputString('');

        $app = new DebugAppMock();
        $app->logger = $app;

        $m = new DebugMock();
        $m->setApp($app);
        $m->log('warning', 'debug test3');

        static::assertSame(['warning', 'debug test3', []], $app->log);
    }

    protected function triggerDebugTraceChange(DebugMock $o, string $trace): void
    {
        $o->debugTraceChange($trace);
    }

    public function testTraceChange(): void
    {
        $app = new DebugAppMock();

        $m = new DebugMock();
        $app->logger = $app;
        $m->setApp($app);

        $this->triggerDebugTraceChange($m, 'test1'); // difference is 1 line between calls
        $this->triggerDebugTraceChange($m, 'test1');

        $pattern = '/Call path for .* has diverged \(was (.*):(.*), now (.*):(.*)\)/';
        $matches = [];
        preg_match($pattern, $app->log[1], $matches);

        // Changes detected
        static::assertTrue(is_array($matches));
        static::assertCount(5, $matches);
        static::assertSame($matches[1], $matches[3]);
        static::assertSame((string) ($matches[2] + 1), $matches[4]);

        $app->log = null;

        for ($i = 1; $i < 5; ++$i) {
            $this->triggerDebugTraceChange($m, 'test2'); // called from same line all 5 times = no difference
        }

        // No changes in the trace change detected
        static::assertNull($app->log);
    }

    public function testPsr(): void
    {
        $app = new DebugAppMock();

        $m = new DebugPsrMock();
        $app->logger = $app;
        $m->setApp($app);

        $m->info('i', ['x']);
        static::assertSame(['info', 'i', ['x']], $app->log);

        $m->warning('t', ['x']);
        static::assertSame(['warning', 't', ['x']], $app->log);

        $m->emergency('em', ['x', 'y']);
        static::assertSame(['emergency', 'em', ['x', 'y']], $app->log);

        $m->alert('al', ['x']);
        static::assertSame(['alert', 'al', ['x']], $app->log);

        $m->critical('cr', ['x']);
        static::assertSame(['critical', 'cr', ['x']], $app->log);

        $m->error('er', ['x']);
        static::assertSame(['error', 'er', ['x']], $app->log);

        $m->notice('nt', ['x']);
        static::assertSame(['notice', 'nt', ['x']], $app->log);
    }
}

class DebugMock
{
    use AppScopeTrait;
    use DebugTrait;

    protected function _echoStderr(string $message): void
    {
        echo $message;
    }
}

class DebugAppMock implements \Psr\Log\LoggerInterface
{
    use \Psr\Log\LoggerTrait;

    /** @var array<int, mixed>|null */
    public $log;
    /** @var self */
    public $logger;

    /**
     * @param mixed  $level
     * @param string $message
     */
    public function log($level, $message, array $context = [])
    {
        $this->log = [$level, $message, $context];
    }
}

class DebugPsrMock implements \Psr\Log\LoggerInterface
{
    use AppScopeTrait;
    use DebugTrait;
}
