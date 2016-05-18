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
        $anon = $m->add(new TrackableMock());
        $m->add(new TrackableMock(), 'foo bar');
        $m->add(new TrackableMock(), '123');
        $m->add(new TrackableMock(), 'false');

        $this->assertEquals(true, (boolean)$m->hasElement('foo bar'));
        $this->assertEquals(true, (boolean)$m->hasElement('123'));
        $this->assertEquals(true, (boolean)$m->hasElement('false'));
        $this->assertEquals(5, $m->getElementCount());


        $m->getElement('foo bar')->destroy();
        $this->assertEquals(4, $m->getElementCount());
        $anon->destroy();
        $this->assertEquals(3, $m->getElementCount());
    }

    public function testLongNames()
    {
        $app = new ContainerAppMock();
        $app->app = $app;
        $app->max_name_length=30;
        $m = $app->add(new ContainerAppMock(), 'quick-brouwn-fox');
        $m = $m->add(new ContainerAppMock(), 'jumps-over-a-lazy-dog');
        $m = $m->add(new ContainerAppMock(), 'then-they-go-out-for-a-pint');
        $m = $m->add(new ContainerAppMock(), 'eat-a-stake');
        $x=$m->add(new ContainerAppMock(), 'with');
        $x=$m->add(new ContainerAppMock(), 'a');
        $x=$m->add(new ContainerAppMock(), 'mint');

        $this->assertEquals(
            '_quick-brouwn-fox_jumps-over-a-lazy-dog_then-they-go-out-for-a-pint_eat-a-stake', 
            $m->unshortenName($this)
        );

        $this->assertLessThan(5, count($app->unique_hashes));
        $this->assertGreaterThan(2, count($app->unique_hashes));

        $m->removeElement($x);
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

    /**
     * @expectedException     Exception
     */
    public function testException3()
    {
        $m = new ContainerMock();
        $m->add('hello', 123);
    }
}

class ContainerMock {
    use core\ContainerTrait;


    function getElementCount()
    {
        return count($this->elements);
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
    use core\TrackableTrait;
    function add($obj, $args = [])
    {
        return $this->_add_Container($obj, $args);
    }
    function unshortenName()
    {
        $n = $this->name;

        $d = array_flip($this->app->unique_hashes);

        for($x=1; $x < 100; $x++) {
            @list($l,$r) = explode('__',$n);

            if(!$r){
                return $l;
            }

            $l = $d[$l];
            $n = $l.$r;
        }
    }
}
