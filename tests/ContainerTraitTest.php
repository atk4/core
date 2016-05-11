<?php
namespace atk4\core\tests;

use atk4\core;

/**
 * @coversDefaultClass \atk4\data\Model
 */
class ContainerTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     */
    public function testBasic()
    {
        $m = new ContainerMock();
        $this->assertEquals(true, isset($m->_containerTrait));

        // add to return object
        $tr = $m->add($tr2 = new \StdClass());
        $this->assertEquals($tr, $tr2);

        // trackable object can be referenced by name
        $m->add($tr3 = new TrackableMock(), 'foo');
        $tr = $m->getElement('foo');
        $this->assertEquals($tr, $tr3);
    }

    public function testUniqueNames()
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock());
        $m->add(new TrackableMock());
        $m->add(new TrackableMock(), 'foo bar');
        $m->add(new TrackableMock(), '123');
        $m->add(new TrackableMock(), 'false');

        $this->assertEquals(true, (boolean)$m->hasElement('foo bar'));
        $this->assertEquals(true, (boolean)$m->hasElement('123'));
        $this->assertEquals(true, (boolean)$m->hasElement('false'));
    }

    /**
     * @expectedException     Exception
     */
    public function testExceptionExists()
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 'foo');
        $m->add(new TrackableMock(), 'foo');
    }

    /**
     * @expectedException     Exception
     */
    public function testExceptionArg2()
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 123);
    }
}

class ContainerMock {
    use core\ContainerTrait;

    function add($obj, $args = []) {
        return $this->_add_Container($obj, $args);
    }
}

class TrackableMock {
    use core\TrackableTrait;
}

class InitializerMock {
    use core\InitializerTrait;
}

class ContainerAppMock {
    use core\ContainerTrait;
    use core\AppScopeTrait;
}
