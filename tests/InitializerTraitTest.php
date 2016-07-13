<?php

namespace atk4\core\tests;

use atk4\core;

class InitializerTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testBasic()
    {
        $m = new ContainerMock2();
        $i = $m->add(new InitializerMock());

        $this->assertEquals(true, $i->result);
    }

    /**
     * @expectedException     Exception
     */
    public function testInitializerNotCalled()
    {
        $m = new ContainerMock2();
        $m->add(new BrokenInitializerMock());
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

    public function init()
    {
        parent::init();

        $this->result = true;
    }
}


class BrokenInitializerMock extends _InitializerMock
{
    public function init()
    {
        // do not call parent
    }
}
