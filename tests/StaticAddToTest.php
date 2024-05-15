<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\StaticAddToTrait;
use Atk4\Core\TrackableTrait;

class StdSat extends \stdClass
{
    use StaticAddToTrait;
}

class StdSat2 extends StdSat {}

class ContainerFactoryMockSat
{
    use ContainerTrait;
}

class TrackableMockSat
{
    use StaticAddToTrait;
    use TrackableTrait;
}
class DiMockSat
{
    use StaticAddToTrait;

    public ?string $a = 'AAA';
    public ?string $b = 'BBB';
    public ?string $c = null;
}

class DiConstructorMockSat
{
    use StaticAddToTrait;

    public ?string $a = 'AAA';
    public ?string $b = 'BBB';
    public ?string $c = null;

    public function __construct(string $name)
    {
        $this->c = $name;
    }
}

class StaticAddToTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock();

        // add to return object
        $tr = StdSat::addTo($m);
        self::assertSame(StdSat::class, get_class($tr));

        // trackable object can be referenced by name
        $tr3 = TrackableMockSat::addTo($m, [], ['foo']);
        $tr = $m->getElement('foo');
        self::assertSame($tr, $tr3);

        // not the same or extended class
        $this->expectException(\TypeError::class);
        StdSat::addTo($m, $tr); // @phpstan-ignore argument.type
    }

    public function testAssertInstanceOf(): void
    {
        // object is of the same class
        StdSat::assertInstanceOf(new StdSat());
        $o = new StdSat();
        self::assertSame($o, StdSat::assertInstanceOf($o));

        // object is a subtype
        StdSat::assertInstanceOf(new StdSat2());

        // object is not a subtype
        $this->expectException(Exception::class);
        StdSat2::assertInstanceOf(new StdSat());
    }

    public function testWithClassName(): void
    {
        $m = new ContainerMock();

        // the same class
        $tr = StdSat::addToWithCl($m, [StdSat::class]);
        self::assertSame(StdSat::class, get_class($tr));

        // add object - for BC
        $tr = StdSat::addToWithCl($m, $tr);
        self::assertSame(StdSat::class, get_class($tr));

        // extended class
        $tr = StdSat::addToWithCl($m, [StdSat2::class]);
        self::assertSame(StdSat2::class, get_class($tr));

        // not the same or extended class - unsafe enabled
        $tr = StdSat::addToWithClUnsafe($m, [\stdClass::class]);
        self::assertSame(\stdClass::class, get_class($tr));

        // not the same or extended class - unsafe disabled
        $this->expectException(Exception::class);
        StdSat::addToWithCl($m, [\stdClass::class]);
    }

    public function testUniqueNames(): void
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names assigned.
        TrackableMockSat::addTo($m);
        $anon = TrackableMockSat::addTo($m);
        TrackableMockSat::addTo($m, [], ['foo bar']);
        TrackableMockSat::addTo($m, [], ['123']);
        TrackableMockSat::addTo($m, [], ['false']);

        self::assertTrue($m->hasElement('foo bar'));
        self::assertTrue($m->hasElement('123'));
        self::assertTrue($m->hasElement('false'));
        self::assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        self::assertSame(4, $m->getElementCount());
        $anon->destroy();
        self::assertSame(3, $m->getElementCount());
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMockSat();
        $m1 = DiMockSat::addTo($m, ['a' => 'XXX', 'b' => 'YYY']);
        self::assertSame('XXX', $m1->a);
        self::assertSame('YYY', $m1->b);
        self::assertNull($m1->c);

        $m2 = DiConstructorMockSat::addTo($m, ['a' => 'XXX', 'John', 'b' => 'YYY']);
        self::assertSame('XXX', $m2->a);
        self::assertSame('YYY', $m2->b);
        self::assertSame('John', $m2->c);
    }
}
