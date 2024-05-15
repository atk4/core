<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\ContainerTrait;
use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\InitializerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class ContainerTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock();

        // add to return object
        $tr2 = new \stdClass();
        $tr = $m->add($tr2);
        self::assertSame($tr, $tr2);

        // trackable object can be referenced by name
        $tr3 = new TrackableMock();
        $m->add($tr3, 'foo');
        $tr = $m->getElement('foo');
        self::assertSame($tr, $tr3);
    }

    public function testUniqueNames(): void
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names assigned.
        $m->add(new TrackableMock());
        $anon = $m->add(new TrackableMock());
        $m->add(new TrackableMock(), 'foo bar');
        $m->add(new TrackableMock(), '123');
        $m->add(new TrackableMock(), 'false');

        self::assertTrue($m->hasElement('foo bar'));
        self::assertTrue($m->hasElement('123'));
        self::assertTrue($m->hasElement('false'));
        self::assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        self::assertSame(4, $m->getElementCount());
        $anon->destroy();
        self::assertSame(3, $m->getElementCount());
    }

    public function testLongNames(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->maxNameLength = 40;
        $m = $app->add(new ContainerAppMock(), 'quick-brown-fox');
        $m = $m->add(new ContainerAppMock(), 'jumps-over-a-lazy-dog');
        $m = $m->add(new ContainerAppMock(), 'then-they-go-out-for-a-pint');
        $m = $m->add(new ContainerAppMock(), 'eat-a-stake');
        $x = $m->add(new ContainerAppMock(), 'with');
        $x = $m->add(new ContainerAppMock(), 'a');
        $x = $m->add(new ContainerAppMock(), 'mint');

        self::assertSame(
            '_quick-brown-fox_jumps-over-a-lazy-dog_then-they-go-out-for-a-pint_eat-a-stake',
            $m->unshortenName($this)
        );

        self::assertLessThan(5, count($app->uniqueNameHashes));
        self::assertGreaterThan(2, count($app->uniqueNameHashes));

        $m->removeElement($x);

        self::assertSame(2, $m->getElementCount());
        $m->add(new \stdClass());

        self::assertSame(2, $m->getElementCount());
    }

    public function testLongNames2(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->maxNameLength = 40;
        $app->name = 'my-app-name-is-pretty-long';

        $minLength = 9999;
        $minLengthValue = '';
        $maxLength = 0;
        $maxLengthValue = '';

        for ($x = 1; $x < 100; ++$x) {
            $sh = str_repeat('x', $x);
            $m = $app->add(new ContainerAppMock(), $sh);
            if (strlen($m->name) > $maxLength) {
                $maxLength = strlen($m->name);
                $maxLengthValue = $m->name;
            }
            if (strlen($m->name) < $minLength) {
                $minLength = strlen($m->name);
                $minLengthValue = $m->name;
            }
        }

        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        self::assertGreaterThanOrEqual(15, $minLength);
        // hash is 10 and we want 5 chars minimum for the right side e.g. XYXYXYXY__abcde
        self::assertLessThanOrEqual($app->maxNameLength, $maxLength);
    }

    public function testPreservePresetNames(): void
    {
        $app = new ContainerAppMock();
        $app->setApp($app);
        $app->name = 'r';
        $app->maxNameLength = 40;

        $createTrackableMockFx = function (string $name, bool $isLongName = false) {
            return new class($name, $isLongName) extends TrackableMock {
                use NameTrait;

                public function __construct(string $name, bool $isLongName)
                {
                    if ($isLongName) {
                        $this->name = $name;
                    } else {
                        $this->shortName = $name;
                    }
                }
            };
        };

        self::assertSame('r_foo', $app->add($createTrackableMockFx('foo'))->name);
        self::assertSame('r_bar', $app->add($createTrackableMockFx('bar'))->name);
        self::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100)))->name));
        self::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100)))->name));

        self::assertSame('foo', $app->add($createTrackableMockFx('foo', true))->name);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element has too long desired name');
        self::assertSame(40, strlen($app->add($createTrackableMockFx(str_repeat('x', 100), true))->name));
    }

    public function testOwnerNotSetException(): void
    {
        $m = new TrackableMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Owner is not set');
        $m->getOwner();
    }

    public function testOwnerSetTwiceException(): void
    {
        $m = new TrackableMock();
        $owner = new \stdClass();
        $m->setOwner($owner);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Owner is already set');
        $m->setOwner($owner);
    }

    public function testOwnerUnset(): void
    {
        $m = new TrackableMock();
        $owner = new \stdClass();
        $m->setOwner($owner);
        self::assertSame($owner, $m->getOwner());
        $m->unsetOwner();

        $owner = new \stdClass();
        $m->setOwner($owner);
        self::assertSame($owner, $m->getOwner());
        $m->unsetOwner();
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMock();
        $m2 = $m->add([ContainerMock::class]);
        self::assertSame(ContainerMock::class, get_class($m2));

        $m3 = $m->add([TrackableContainerMock::class], 'name');
        self::assertSame(TrackableContainerMock::class, get_class($m3));
        self::assertSame('name', $m3->shortName);
    }

    public function testArgs(): void
    {
        // passing name with array key 'name'
        $m = new ContainerMock();
        $m2 = $m->add(new class() extends TrackableMock {
            use DiContainerTrait;
            use NameTrait;
        }, ['name' => 'foo']);
        self::assertTrue($m->hasElement('foo'));
        self::assertSame('foo', $m2->shortName);
    }

    public function testExceptionExists(): void
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), 'foo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element with requested name already exists');
        $m->add(new TrackableMock(), 'foo');
    }

    public function testDesiredName(): void
    {
        $m = new ContainerMock();
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);
        $m->add(new TrackableMock(), ['desired_name' => 'foo']);

        self::assertTrue($m->hasElement('foo'));
    }

    public function testExceptionShortName(): void
    {
        $m1 = new ContainerMock();
        $m2 = new ContainerMock();
        $m1foo = $m1->add(new TrackableMock(), 'foo');
        $m2foo = $m2->add(new TrackableMock(), 'foo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element with requested name already exists');
        $m2->add($m1foo); // will carry on short name and run into collision
    }

    public function testExceptionArg2(): void
    {
        $m = new ContainerMock();

        if (\PHP_MAJOR_VERSION === 7) {
            self::assertNotNull('Expecting E_WARNING is deprecated in PHPUnit 9'); // @phpstan-ignore staticMethod.alreadyNarrowedType

            return;
        }

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('array_diff_key(): Argument #1 ($array) must be of type array, int given');
        $m->add(new TrackableMock(), 123); // @phpstan-ignore argument.type
    }

    public function testException3(): void
    {
        $m = new ContainerMock();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage(\PHP_MAJOR_VERSION === 7 ? 'Class \'hello\' not found' : 'Class "hello" not found');
        $m->add(['hello']);
    }

    public function testException4(): void
    {
        $m = new ContainerMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Child element not found');
        $m->getElement('dont_exist');
    }

    public function testException5(): void
    {
        $m = new ContainerMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Child element not found');
        $m->removeElement('dont_exist');
    }

    public function testParentInitNotCalledException(): void
    {
        $m = new ContainerMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object was not initialized');
        $m->add(new class() extends TrackableMock {
            use InitializerTrait;

            protected function init(): void {}
        });
    }

    public function testExceptionInInitMustNotAdd(): void
    {
        $m = new ContainerMock();

        $e = null;
        try {
            $m->add(new class() extends TrackableMock {
                use InitializerTrait;

                protected function init(): void
                {
                    throw new Exception('from init');
                }
            }, 'foo');
        } catch (Exception $e) {
        }
        self::assertSame('from init', $e->getMessage());

        self::assertFalse($m->hasElement('foo'));
        $m->add(new TrackableMock(), 'foo');
        self::assertTrue($m->hasElement('foo'));
    }
}

class TrackableMock
{
    use TrackableTrait;
}
class ContainerFactoryMock
{
    use ContainerTrait;
    use NameTrait;
}

class TrackableContainerMock
{
    use ContainerTrait;
    use TrackableTrait;
}

class ContainerAppMock
{
    use AppScopeTrait;
    use ContainerTrait;
    use NameTrait;
    use TrackableTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }

    public function unshortenName(): string
    {
        $n = $this->name;

        $d = array_flip($this->getApp()->uniqueNameHashes);

        for ($x = 0; str_contains($n, '__') && $x < 100; ++$x) {
            [$l, $r] = explode('__', $n);
            $l = $d[$l];
            $n = $l . $r;
        }

        return $n;
    }
}
