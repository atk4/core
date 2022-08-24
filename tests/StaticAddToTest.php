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

class StdSat2 extends StdSat
{
}

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

    /** @var string */
    public $a = 'AAA';
    /** @var string */
    public $b = 'BBB';
    /** @var string */
    public $c;
}

class DiConstructorMockSat
{
    use StaticAddToTrait;

    /** @var string */
    public $a = 'AAA';
    /** @var string */
    public $b = 'BBB';
    /** @var string */
    public $c;

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
        static::assertNotNull($tr);

        // trackable object can be referenced by name
        $tr3 = TrackableMockSat::addTo($m, [], ['foo']);
        $tr = $m->getElement('foo');
        static::assertSame($tr, $tr3);

        // not the same or extended class
        $this->expectException(\TypeError::class);
        StdSat::addTo($m, $tr); // @phpstan-ignore-line
    }

    public function testAssertInstanceOf(): void
    {
        // object is of the same class
        StdSat::assertInstanceOf(new StdSat());
        $o = new StdSat();
        static::assertSame($o, StdSat::assertInstanceOf($o));

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
        static::assertSame(StdSat::class, get_class($tr));

        // add object - for BC
        $tr = StdSat::addToWithCl($m, $tr);
        static::assertSame(StdSat::class, get_class($tr));

        // extended class
        $tr = StdSat::addToWithCl($m, [StdSat2::class]);
        static::assertSame(StdSat2::class, get_class($tr));

        // not the same or extended class - unsafe enabled
        $tr = StdSat::addToWithClUnsafe($m, [\stdClass::class]);
        static::assertSame(\stdClass::class, get_class($tr));

        // not the same or extended class - unsafe disabled
        $this->expectException(Exception::class);
        StdSat::addToWithCl($m, [\stdClass::class]);
    }

    public function testUniqueNames(): void
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names asigned.
        TrackableMockSat::addTo($m);
        $anon = TrackableMockSat::addTo($m);
        TrackableMockSat::addTo($m, [], ['foo bar']);
        TrackableMockSat::addTo($m, [], ['123']);
        TrackableMockSat::addTo($m, [], ['false']);

        static::assertTrue($m->hasElement('foo bar'));
        static::assertTrue($m->hasElement('123'));
        static::assertTrue($m->hasElement('false'));
        static::assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        static::assertSame(4, $m->getElementCount());
        $anon->destroy();
        static::assertSame(3, $m->getElementCount());
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMockSat();
        $m1 = DiMockSat::addTo($m, ['a' => 'XXX', 'b' => 'YYY']);
        static::assertSame('XXX', $m1->a);
        static::assertSame('YYY', $m1->b);
        static::assertNull($m1->c);

        $m2 = DiConstructorMockSat::addTo($m, ['a' => 'XXX', 'John', 'b' => 'YYY']);
        static::assertSame('XXX', $m2->a);
        static::assertSame('YYY', $m2->b);
        static::assertSame('John', $m2->c);
    }
}
