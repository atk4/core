<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\AtkPhpunit;
use Atk4\Core\Exception;

/**
 * @coversDefaultClass \Atk4\Core\InitializerTrait
 */
class InitializerTraitTest extends AtkPhpunit\TestCase
{
    public function testBasic()
    {
        $m = new ContainerMock2();
        $i = $m->add(new InitializerMock());

        $this->assertTrue($i->result);
    }

    public function testInitializerNotCalled()
    {
        $this->expectException(Exception::class);
        $m = new ContainerMock2();
        $m->add(new BrokenInitializerMock());
    }

    public function testInitializedTwice()
    {
        $this->expectException(Exception::class);
        $m = new InitializerMock();
        $m->invokeInit();
        $m->invokeInit();
    }
}

class ContainerMock2
{
    use core\ContainerTrait;
}

class _InitializerMock
{
    use core\InitializerTrait;
}

class InitializerMock extends _InitializerMock
{
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
