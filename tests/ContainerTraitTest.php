<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\Phpunit\TestCase;

/**
 * @coversDefaultClass \Atk4\Core\ContainerTrait
 */
class ContainerTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock();

        // add to return object
        $tr = $m->add($tr2 = new \StdClass());
        $this->assertSame($tr, $tr2);

        // trackable object can be referenced by name
        $m->add($tr3 = new TrackableMock(), 'foo');
        $tr = $m->getElement('foo');
        $this->assertSame($tr, $tr3);
    }

    public function testUniqueNames(): void
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names asigned.
        $m->add(new TrackableMock());
        $anon = $m->add(new TrackableMock());
        $m->add(new TrackableMock(), 'foo bar');
        $m->add(new TrackableMock(), '123');
        $m->add(new TrackableMock(), 'false');

        $this->assertTrue($m->hasElement('foo bar'));
        $this->assertTrue($m->hasElement('123'));
        $this->assertTrue($m->hasElement('false'));
        $this->assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        $this->assertSame(4, $m->getElementCount());
        $anon->destroy();
        $this->assertSame(3, $m->getElementCount());
    }

    public function testLongNames(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->max_name_length = 40;
        $m = $app->add(new ContainerAppMock(), 'quick-brown-fox');
        $m = $m->add(new ContainerAppMock(), 'jumps-over-a-lazy-dog');
        $m = $m->add(new ContainerAppMock(), 'then-they-go-out-for-a-pint');
        $m = $m->add(new ContainerAppMock(), 'eat-a-stake');
        $x = $m->add(new ContainerAppMock(), 'with');
        $x = $m->add(new ContainerAppMock(), 'a');
        $x = $m->add(new ContainerAppMock(), 'mint');

        $this->assertSame(
            '_quick-brown-fox_jumps-over-a-lazy-dog_then-they-go-out-for-a-pint_eat-a-stake',
            $m->unshortenName($this)
        );

        $this->assertLessThan(5, count($app->unique_hashes));
        $this->assertGreaterThan(2, count($app->unique_hashes));

        $m->removeElement($x);

        $this->assertSame(2, $m->getElementCount());
        $m->add(new \StdClass());

        $this->assertSame(2, $m->getElementCount());
    }

    public function testLongNames2(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->max_name_length = 40;
        $app->name = 'my-app-name-is-pretty-long';

        $max_len = 0;
        $min_len_v = '';
        $min_len = 99;
        $max_len_v = '';

        for ($x = 1; $x < 100; ++$x) {
            $sh = str_repeat('x', $x);
            $m = $app->add(new ContainerAppMock(), $sh);
            if (strlen($m->name) > $max_len) {
                $max_len = strlen($m->name);
                $max_len_v = $m->name;
            }
            if (strlen($m->name) < $min_len) {
                $min_len = strlen($m->name);
                $min_len_v = $m->name;
            }
        }

        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        $this->assertGreaterThanOrEqual(15, $min_len);
        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        $this->assertLessThanOrEqual($app->max_name_length, $max_len);
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMock();
        $m2 = $m->add([ContainerMock::class]);
        $this->assertSame(ContainerMock::class, get_class($m2));

        $m3 = $m->add([TrackableContainerMock::class], 'name');
        $this->assertSame(TrackableContainerMock::class, get_class($m3));
        $this->assertSame('name', $m3->short_name);
    }

    public function testArgs(): void
    {
        // passing name with array key 'name'
        $m = new ContainerMock();
        $m2 = $m->add(new class() extends TrackableMock {
            use Core\DiContainerTrait;
        }, ['name' => 'foo']);
        $this->assertTrue($m->hasElement('foo'));
        $this->assertSame('foo', $m2->short_name);
    }

    public function testExceptionExists(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 'foo');
        $m->add(new TrackableMock(), 'foo');
    }

    public function testDesiredName(): void
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);

        $this->assertTrue($m->hasElement('foo'));
    }

    public function testExceptionShortName(): void
    {
        $this->expectException(Core\Exception::class);
        $m1 = new ContainerMock();
        $m2 = new ContainerMock();
        $m1foo = $m1->add(new TrackableMock(), 'foo');
        $m2foo = $m2->add(new TrackableMock(), 'foo');

        // will carry on short name and run into collision.
        $m2->add($m1foo);
    }

    public function testExceptionArg2(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 123); // @phpstan-ignore-line
    }

    public function testException3(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(\PHP_MAJOR_VERSION < 8 ? 'Class \'hello\' not found' : 'Class "hello" not found');
        $m = new ContainerMock();
        $m->add(['hello'], 123); // @phpstan-ignore-line
    }

    public function testException4(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $el = $m->getElement('dont_exist');
    }

    public function testException5(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new ContainerMock();
        $m->removeElement('dont_exist');
    }
}

class TrackableMock
{
    use Core\TrackableTrait;
}
class ContainerFactoryMock
{
    use Core\ContainerTrait;
    use Core\NameTrait;
}

class ContainerAppMock
{
    use Core\AppScopeTrait;
    use Core\ContainerTrait;
    use Core\TrackableTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }

    public function unshortenName(): string
    {
        $n = $this->name;

        $d = array_flip($this->getApp()->unique_hashes);

        for ($x = 0; strpos($n, '__') !== false && $x < 100; ++$x) {
            [$l, $r] = explode('__', $n);
            $l = $d[$l];
            $n = $l . $r;
        }

        return $n;
    }
}
