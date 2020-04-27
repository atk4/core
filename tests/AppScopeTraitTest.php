<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\AtkPhpunit;
use atk4\core\ContainerTrait;
use atk4\core\NameTrait;
use atk4\core\TrackableTrait;

/**
 * @coversDefaultClass \atk4\core\AppScopeTrait
 */
class AppScopeTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
    public function testConstruct()
    {
        $m = new AppScopeMock();
        $m->app = 'myapp';

        $c = $m->add(new Child1());
        $this->assertSame('myapp', $c->app);

        $c = $m->add(new Child2());
        $this->assertFalse(isset($c->app));

        $m = new AppScopeMock2();

        $c = $m->add(new Child1());
        $this->assertFalse(isset($c->app));

        // test for GC
        $m = new AppScopeMock();
        $m->app = $m;
        $m->add($child = new Child3());
        $child->destroy();
        $this->assertNull($child->app);
        $this->assertNull($child->owner);
    }
}

// @codingStandardsIgnoreStart
class AppScopeMock
{
    use AppScopeTrait;
    use ContainerTrait;
    use NameTrait;

    public function add($obj, $args = []): object
    {
        return $this->_add_Container($obj, $args);
    }
}

class AppScopeMock2
{
    use ContainerTrait;

    public function add($obj, $args = []): object
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

class Child3
{
    use AppScopeTrait;
    use TrackableTrait;
}
// @codingStandardsIgnoreEnd
