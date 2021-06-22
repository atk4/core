<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;

/**
 * @coversDefaultClass \Atk4\Core\InitializerTrait
 */
class InitializerTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock2();
        $i = $m->add(new InitializerMock());

        $this->assertTrue($i->result);
    }

    public function testInitializerNotCalled(): void
    {
        $this->expectException(Exception::class);
        $m = new ContainerMock2();
        $m->add(new BrokenInitializerMock());
    }

    public function testInitializedTwice(): void
    {
        $this->expectException(Exception::class);
        $m = new InitializerMock();
        $m->invokeInit();
        $m->invokeInit();
    }
}

class ContainerMock2
{
    use Core\ContainerTrait;
}

class _InitializerMock
{
    use Core\InitializerTrait;
}

class InitializerMock extends _InitializerMock
{
    /** @var bool */
    public $result = false;

    protected function init(): void
    {
        parent::init();

        $this->result = true;
    }
}

class BrokenInitializerMock extends _InitializerMock
{
    protected function init(): void
    {
        // do not call parent
    }
}
