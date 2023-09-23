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

        self::assertFalse($m->debug);

        $m->debug(true);
        self::assertTrue($m->debug);

        $m->debug(false);
        self::assertFalse($m->debug);

        $m->debug(true);
        self::assertTrue($m->debug);
    }

    public function testDebugOutput(): void
    {
        $m = new DebugMock();
        $m->debug(true);

        $this->expectOutputString("[Atk4\\Core\\Tests\\DebugMock]: debug test1\n");
        $m->debug('debug test1');
    }

    public function testDebugNoOutput(): void
    {
        $m = new DebugMock();

        $this->expectOutputString('');
        $m->debug('debug test2');
    }

    public function testDebugApp(): void
    {
        $app = new DebugAppMock();
        $app->logger = $app;

        $m = new DebugMock();
        $m->setApp($app);
        $m->debug(true);

        $this->expectOutputString('');
        $m->debug('debug test2');

        self::assertSame(['debug', 'debug test2', []], $app->log);
    }

    public function testLog1(): void
    {
        $m = new DebugMock();

        $this->expectOutputString("debug test3\n");
        $m->log('warning', 'debug test3');
    }

    public function testLog2(): void
    {
        $app = new DebugAppMock();
        $app->logger = $app;

        $m = new DebugMock();
        $m->setApp($app);

        $this->expectOutputString('');
        $m->log('warning', 'debug test3');

        self::assertSame(['warning', 'debug test3', []], $app->log);
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

        // changes detected
        preg_match('~Call path for .* has diverged \(was (.*):(.*), now (.*):(.*)\)~', $app->log[1], $matches);
        self::assertCount(5, $matches);
        self::assertSame($matches[1], $matches[3]);
        self::assertSame((string) ((int) $matches[2] + 1), $matches[4]);

        $app->log = null;

        for ($i = 1; $i < 5; ++$i) {
            $this->triggerDebugTraceChange($m, 'test2'); // called from same line all 5 times = no difference
        }

        // no changes in the trace change detected
        self::assertNull($app->log);
    }

    public function testPsr(): void
    {
        $app = new DebugAppMock();

        $m = new DebugPsrMock();
        $app->logger = $app;
        $m->setApp($app);

        $m->info('i', ['x']);
        self::assertSame(['info', 'i', ['x']], $app->log);

        $m->warning('t', ['x']);
        self::assertSame(['warning', 't', ['x']], $app->log);

        $m->emergency('em', ['x', 'y']);
        self::assertSame(['emergency', 'em', ['x', 'y']], $app->log);

        $m->alert('al', ['x']);
        self::assertSame(['alert', 'al', ['x']], $app->log);

        $m->critical('cr', ['x']);
        self::assertSame(['critical', 'cr', ['x']], $app->log);

        $m->error('er', ['x']);
        self::assertSame(['error', 'er', ['x']], $app->log);

        $m->notice('nt', ['x']);
        self::assertSame(['notice', 'nt', ['x']], $app->log);
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

    public function log($level, $message, array $context = []): void
    {
        $this->log = [$level, $message, $context];
    }
}

class DebugPsrMock implements \Psr\Log\LoggerInterface
{
    use AppScopeTrait;
    use DebugTrait;
}
