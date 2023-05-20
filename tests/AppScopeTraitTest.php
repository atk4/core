<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\ContainerTrait;
use Atk4\Core\Exception;
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
        self::assertSame($fakeApp, $c->getApp());

        $c = $m->add(new AppScopeChildWithoutAppScope());
        self::assertFalse(property_exists($c, 'app'));
        self::assertFalse(property_exists($c, '_app'));

        $m = new AppScopeMock2();

        $c = $m->add(new AppScopeChildBasic());
        self::assertFalse($c->issetApp());

        // test for GC
        $m = new AppScopeMock();
        $m->setApp($m);
        $child = new AppScopeChildTrackable();
        $m->add($child);
        $child->destroy();
        self::assertNull($this->getProtected($child, '_app'));
        self::assertFalse($child->issetOwner());
    }

    public function testAppNotSetException(): void
    {
        $m = new AppScopeMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('App is not set');
        $m->getApp();
    }

    public function testAppSetTwiceException(): void
    {
        $m = new AppScopeMock();
        $fakeApp = new \stdClass();
        $m->setApp($fakeApp);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('App is already set');
        $m->setApp($fakeApp);
    }
}

class AppScopeMock
{
    use AppScopeTrait;
    use ContainerTrait;
    use NameTrait;

    /**
     * @param array{desired_name?: string, name?: string} $args
     */
    public function add(object $obj, array $args = []): object
    {
        $this->_addContainer($obj, $args);

        return $obj;
    }
}

class AppScopeMock2
{
    use ContainerTrait;

    /**
     * @param array{desired_name?: string, name?: string} $args
     */
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
