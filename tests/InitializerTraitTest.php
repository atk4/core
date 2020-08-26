<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core;
use atk4\core\AtkPhpunit;
use atk4\core\Exception;

/**
 * @coversDefaultClass \atk4\core\InitializerTrait
 */
class InitializerTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
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

// @codingStandardsIgnoreStart
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
// @codingStandardsIgnoreEnd
