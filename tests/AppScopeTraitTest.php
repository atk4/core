<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\NameTrait;
use atk4\core\TrackableTrait;

/**
 * @coversDefaultClass \atk4\core\AppScopeTrait
 */
class AppScopeTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testConstruct()
    {
        $m = new AppScopeMock();
        $m->app = 'myapp';

        $c = $m->add(new Child1());
        $this->assertEquals('myapp', $c->app);

        $c = $m->add(new Child2());
        $this->assertEquals(false, isset($c->app));

        $m = new AppScopeMock2();

        $c = $m->add(new Child1());
        $this->assertEquals(false, isset($c->app));
    }
}

// @codingStandardsIgnoreStart
class AppScopeMock
{
    use AppScopeTrait;
    use ContainerTrait;
    use NameTrait;

    public function add($obj, $args = [])
    {
        return $this->_add_Container($obj, $args);
    }
}

class AppScopeMock2
{
    use ContainerTrait;

    public function add($obj, $args = [])
    {
        return $this->_add_Container($obj, $args);
    }
}

class Child1
{
    use AppScopeTrait;
}

class Child2
{
    use TrackableTrait;
}
// @codingStandardsIgnoreEnd
