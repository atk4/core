<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ContainerTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\StaticAddToTrait;
use Atk4\Core\TrackableTrait;

class StdSat extends \StdClass
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

/**
 * @coversDefaultClass \Atk4\Core\StaticAddToTrait
 */
class StaticAddToTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new ContainerMock();

        // add to return object
        $tr = StdSat::addTo($m);
        $this->assertNotNull($tr);

        // trackable object can be referenced by name
        $tr3 = TrackableMockSat::addTo($m, [], ['foo']);
        $tr = $m->getElement('foo');
        $this->assertSame($tr, $tr3);

        // not the same or extended class
        $this->expectException(\Atk4\Core\Exception::class);
        $tr = StdSat::addTo($m, $tr); // @phpstan-ignore-line
    }

    public function testAssertInstanceOf(): void
    {
        // object is of the same class
        StdSat::assertInstanceOf(new StdSat());
        $o = new StdSat();
        $this->assertSame($o, StdSat::assertInstanceOf($o));

        // object is a subtype
        StdSat::assertInstanceOf(new StdSat2());

        // object is not a subtype
        $this->expectException(\Atk4\Core\Exception::class);
        StdSat2::assertInstanceOf(new StdSat());
    }

    public function testWithClassName(): void
    {
        $m = new ContainerMock();

        // the same class
        $tr = StdSat::addToWithCl($m, [StdSat::class]);
        $this->assertSame(StdSat::class, get_class($tr));

        // add object - for BC
        $tr = StdSat::addToWithCl($m, $tr);
        $this->assertSame(StdSat::class, get_class($tr));

        // extended class
        $tr = StdSat::addToWithCl($m, [StdSat2::class]);
        $this->assertSame(StdSat2::class, get_class($tr));

        // not the same or extended class - unsafe enabled
        $tr = StdSat::addToWithClUnsafe($m, [\stdClass::class]);
        $this->assertSame(\stdClass::class, get_class($tr));

        // not the same or extended class - unsafe disabled
        $this->expectException(\Atk4\Core\Exception::class);
        $tr = StdSat::addToWithCl($m, [\stdClass::class]);
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

        $this->assertTrue($m->hasElement('foo bar'));
        $this->assertTrue($m->hasElement('123'));
        $this->assertTrue($m->hasElement('false'));
        $this->assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        $this->assertSame(4, $m->getElementCount());
        $anon->destroy();
        $this->assertSame(3, $m->getElementCount());
    }

    public function testFactoryMock(): void
    {
        $m = new ContainerFactoryMockSat();
        $m1 = DiMockSat::addTo($m, ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertNull($m1->c);

        $m2 = DiConstructorMockSat::addTo($m, ['a' => 'XXX', 'John', 'b' => 'YYY']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('John', $m2->c);
    }
}
