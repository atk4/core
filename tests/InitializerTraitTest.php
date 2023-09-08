<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\InitializerTrait;
use Atk4\Core\Phpunit\TestCase;

class InitializerTraitTest extends TestCase
{
    public function testInit(): void
    {
        $m = new InitializerMock();
        self::assertFalse($m->isInitialized());
        $m->invokeInit();
        self::assertTrue($m->isInitialized());
        self::assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitCalledFromAdd(): void
    {
        $container = new class() {
            use ContainerTrait;
        };

        $m = new InitializerMock();
        $container->add($m);
        self::assertTrue($m->isInitialized());
        self::assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitNotCalled(): void
    {
        $m = new InitializerMock();
        self::assertFalse($m->isInitialized());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object was not initialized');
        $m->assertIsInitialized();
    }

    public function testInitNoParentCalledException(): void
    {
        $m = new class() extends AbstractInitializerMock {
            protected function init(): void {}
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object was not initialized');
        $m->invokeInit();
    }

    public function testInitCalledTwiceException(): void
    {
        $m = new InitializerMock();
        $m->invokeInit();
        self::assertTrue($m->isInitialized());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object already initialized');
        $m->invokeInit();
    }

    public function testInitDeclaredPublicException(): void
    {
        $m = new class() extends AbstractInitializerMock {
            public function init(): void {}
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Init method must have protected visibility');
        $m->invokeInit();
    }
}

abstract class AbstractInitializerMock
{
    use InitializerTrait;
}

class InitializerMock extends AbstractInitializerMock
{
    /** @var bool */
    public $result = false;

    protected function init(): void
    {
        parent::init();

        $this->result = true;
    }
}
