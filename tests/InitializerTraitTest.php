<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;

class InitializerTraitTest extends TestCase
{
    public function testInit(): void
    {
        $m = new InitializerMock();
        $this->assertFalse($m->isInitialized());
        $m->invokeInit();
        $this->assertTrue($m->isInitialized());
        $this->assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitCalledFromAdd(): void
    {
        $container = new class() {
            use Core\ContainerTrait;
        };

        $m = new InitializerMock();
        $container->add($m);
        $this->assertTrue($m->isInitialized());
        $this->assertTrue($m->result);
        $m->assertIsInitialized();
    }

    public function testInitNotCalled(): void
    {
        $m = new InitializerMock();
        $this->expectException(Exception::class);
        $this->assertFalse($m->isInitialized());
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
        $this->assertTrue($m->isInitialized());
        $this->expectException(Exception::class);
        $m->invokeInit();
    }
}

abstract class AbstractInitializerMock
{
    use Core\InitializerTrait;
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
