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
        static::assertFalse($m->isInitialized());
        $m->invokeInit();
        static::assertTrue($m->isInitialized());
        static::assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitCalledFromAdd(): void
    {
        $container = new class() {
            use ContainerTrait;
        };

        $m = new InitializerMock();
        $container->add($m);
        static::assertTrue($m->isInitialized());
        static::assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitNotCalled(): void
    {
        $m = new InitializerMock();
        static::assertFalse($m->isInitialized());

        $this->expectException(Exception::class);
        $m->assertIsInitialized();
    }

    public function testInitBroken(): void
    {
        $m = new InitializerMockBroken();

        $this->expectException(Exception::class);
        $m->invokeInit();
    }

    public function testInitCalledTwice(): void
    {
        $m = new InitializerMock();
        $m->invokeInit();
        static::assertTrue($m->isInitialized());

        $this->expectException(Exception::class);
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

class InitializerMockBroken extends AbstractInitializerMock
{
    protected function init(): void
    {
        // do not call parent
    }
}
