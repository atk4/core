<?php
namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\TrackableTrait;

/**
 * @coversDefaultClass \atk4\data\Model
 */
class AppScopeTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     */
    public function testConstruct()
    {
        $m = new AppScopeMock();
        $m->app="myapp";

        $c = $m->add(new Child1());
        $this->assertEquals('myapp', $c->app);
    }

}

class AppScopeMock {
    use AppScopeTrait;
    use ContainerTrait;
    function add($obj, $args = [])
    {
        return $this->_add_Container($obj, $args);
    }
}

class Child1 {
    use AppScopeTrait;
}

class Child2 {
    use TrackableTrait;
}
