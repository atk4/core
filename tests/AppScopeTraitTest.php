<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\ContainerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class AppScopeTraitTest extends TestCase
{
    public function testConstruct(): void
    {
        $m = new AppScopeMock();
        $fakeApp = new \stdClass();
        $m->setApp($fakeApp);

        $c = $m->add(new AppScopeChildBasic());
        static::assertSame($fakeApp, $c->getApp());

        $c = $m->add(new AppScopeChildWithoutAppScope());
        static::assertFalse(property_exists($c, 'app'));
        static::assertFalse(property_exists($c, '_app'));

        $m = new AppScopeMock2();

        $c = $m->add(new AppScopeChildBasic());
        static::assertFalse($c->issetApp());

        // test for GC
        $m = new AppScopeMock();
        $m->setApp($m);
        $m->add($child = new AppScopeChildTrackable());
        $child->destroy();
        static::assertNull($this->getProtected($child, '_app'));
        static::assertFalse($child->issetOwner());
    }
}

class AppScopeMock
{
    use AppScopeTrait;
    use ContainerTrait;
    use NameTrait;

    public function add(object $obj, array $args = []): object
    {
        $this->_addContainer($obj, $args);

        return $obj;
    }
}

class AppScopeMock2
{
    use ContainerTrait;

    public function add(object $obj, array $args = []): object
    {
        $this->_addContainer($obj, $args);

        return $obj;
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
