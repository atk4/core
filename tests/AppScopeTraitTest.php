<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\AtkPhpunit;
use Atk4\Core\ContainerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\TrackableTrait;

/**
 * @coversDefaultClass \Atk4\Core\AppScopeTrait
 */
class AppScopeTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
    public function testConstruct()
    {
        $m = new AppScopeMock();
        $fakeApp = new \stdClass();
        $m->setApp($fakeApp);

        $c = $m->add(new AppScopeChildBasic());
        $this->assertSame($fakeApp, $c->getApp());

        $c = $m->add(new AppScopeChildWithoutAppScope());
        $this->assertFalse(property_exists($c, 'app'));
        $this->assertFalse(property_exists($c, '_app'));

        $m = new AppScopeMock2();

        $c = $m->add(new AppScopeChildBasic());
        $this->assertFalse($c->issetApp());

        // test for GC
        $m = new AppScopeMock();
        $m->setApp($m);
        $m->add($child = new AppScopeChildTrackable());
        $child->destroy();
        $this->assertNull($this->getProtected($child, '_app'));
        $this->assertFalse($child->issetOwner());
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

class AppScopeChildBasic
{
    use AppScopeTrait;
}

class AppScopeChildWithoutAppScope
{
    use TrackableTrait;
}

class AppScopeChildTrackable
{
    use AppScopeTrait;
    use TrackableTrait;
}
// @codingStandardsIgnoreEnd
