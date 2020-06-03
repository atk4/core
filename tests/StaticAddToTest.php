<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\ContainerTrait;
use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;
use atk4\core\StaticAddToTrait;
use atk4\core\TrackableTrait;

// @codingStandardsIgnoreStart
class StdSAT extends \StdClass
{
    use StaticAddToTrait;
}

class StdSAT2 extends StdSAT
{
}

class ContainerFactoryMockSAT
{
    use ContainerTrait;
    use FactoryTrait;
}

class TrackableMockSAT
{
    use TrackableTrait;
    use StaticAddToTrait;
}
class DIMockSAT
{
    use FactoryTrait;
    use DIContainerTrait;
    use StaticAddToTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}

class DIConstructorMockSAT
{
    use FactoryTrait;
    use DIContainerTrait;
    use StaticAddToTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;

    public function __construct($name)
    {
        $this->c = $name;
    }
}
// @codingStandardsIgnoreEnd

/**
 * @coversDefaultClass \atk4\core\StaticAddToTrait
 */
class StaticAddToTest extends AtkPhpunit\TestCase
{
    public function testBasic()
    {
        $m = new ContainerMock();
        $this->assertTrue(isset($m->_containerTrait));

        // create object using default factory
        $this->assertSame(StdSAT::class, get_class(StdSAT::fromSeed()));
        $this->assertSame(StdSAT2::class, get_class(StdSAT::fromSeedWithCl(StdSAT2::class)));
        $this->assertSame(StdSAT2::class, get_class(StdSAT::fromSeedWithCl([StdSAT2::class])));

        // add to return object
        $tr = StdSAT::addTo($m);
        $this->assertNotNull($tr);

        // add object - for BC
        $tr = StdSAT::addTo($m, $tr);
        $this->assertSame(StdSAT::class, get_class($tr));

        // trackable object can be referenced by name
        $tr3 = TrackableMockSAT::addTo($m, [], ['foo']);
        $tr = $m->getElement('foo');
        $this->assertSame($tr, $tr3);

        // not the same or extended class
        $this->expectException(\atk4\core\Exception::class);
        $tr = StdSAT::addTo($m, $tr);
    }

    public function testCheckInstanceOf()
    {
        // object is of the same class
        StdSAT::checkInstanceOf(new StdSAT());
        $o = new StdSAT();
        $this->assertSame($o, StdSAT::checkInstanceOf($o));

        // object is a subtype
        StdSAT::checkInstanceOf(new StdSAT2());

        // object is not a subtype
        $this->expectException(\atk4\core\Exception::class);
        StdSAT2::checkInstanceOf(new StdSAT());
    }

    public function testWithClassName()
    {
        $m = new ContainerMock();
        $this->assertTrue(isset($m->_containerTrait));

        // the same class
        $tr = StdSAT::addToWithCl($m, StdSAT::class);
        $this->assertSame(StdSAT::class, get_class($tr));

        // add object - for BC
        $tr = StdSAT::addToWithCl($m, $tr);
        $this->assertSame(StdSAT::class, get_class($tr));

        // extended class
        $tr = StdSAT::addToWithCl($m, StdSAT2::class);
        $this->assertSame(StdSAT2::class, get_class($tr));

        // not the same or extended class - unsafe disabled
        $this->expectException(\atk4\core\Exception::class);
        $tr = StdSAT::addToWithCl($m, \stdClass::class);

        // not the same or extended class - unsafe enabled
        $tr = StdSAT::addToWithClUnsafe($m, \stdClass::class);
        $this->assertSame(\stdClass::class, get_class($tr));
    }

    public function testUniqueNames()
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names asigned.
        TrackableMockSAT::addTo($m);
        $anon = TrackableMockSAT::addTo($m);
        TrackableMockSAT::addTo($m, [], ['foo bar']);
        TrackableMockSAT::addTo($m, [], ['123']);
        TrackableMockSAT::addTo($m, [], ['false']);

        $this->assertTrue((bool) $m->hasElement('foo bar'));
        $this->assertTrue((bool) $m->hasElement('123'));
        $this->assertTrue((bool) $m->hasElement('false'));
        $this->assertSame(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        $this->assertSame(4, $m->getElementCount());
        $anon->destroy();
        $this->assertSame(3, $m->getElementCount());
    }

    public function testFactoryMock()
    {
        $m = new ContainerFactoryMockSAT();
        $m1 = DIMockSAT::addTo($m, ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertNull($m1->c);

        $m2 = DIConstructorMockSAT::addTo($m, ['a' => 'XXX', 'John', 'b' => 'YYY']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('John', $m2->c);
    }
}
