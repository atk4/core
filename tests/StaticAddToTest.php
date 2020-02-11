<?php

namespace atk4\core\tests;

use atk4\core\ContainerTrait;
use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;
use atk4\core\StaticAddToTrait;
use atk4\core\TrackableTrait;
use PHPUnit\Framework\TestCase;

// @codingStandardsIgnoreStart
class StdSAT extends \StdClass
{
    use StaticAddToTrait;
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
class StaticAddToTest extends TestCase
{
    public function testBasic()
    {
        $m = new ContainerMock();
        $this->assertEquals(true, isset($m->_containerTrait));

        // add to return object
        $tr = StdSAT::addTo($m);
        $this->assertNotNull($tr);

        // trackable object can be referenced by name
        $tr3 = TrackableMockSAT::addTo($m, [], 'foo');
        $tr = $m->getElement('foo');
        $this->assertEquals($tr, $tr3);
    }

    public function testUniqueNames()
    {
        $m = new ContainerMock();

        // two anonymous children should get unique names asigned.
        TrackableMockSAT::addTo($m);
        $anon = TrackableMockSAT::addTo($m);
        TrackableMockSAT::addTo($m, [], 'foo bar');
        TrackableMockSAT::addTo($m, [], '123');
        TrackableMockSAT::addTo($m, [], 'false');

        $this->assertEquals(true, (bool) $m->hasElement('foo bar'));
        $this->assertEquals(true, (bool) $m->hasElement('123'));
        $this->assertEquals(true, (bool) $m->hasElement('false'));
        $this->assertEquals(5, $m->getElementCount());

        $m->getElement('foo bar')->destroy();
        $this->assertEquals(4, $m->getElementCount());
        $anon->destroy();
        $this->assertEquals(3, $m->getElementCount());
    }

    public function testFactoryMock()
    {
        $m = new ContainerFactoryMockSAT();
        $m1 = DIMockSAT::addTo($m, ['a'=>'XXX', 'b'=>'YYY']);
        $this->assertEquals('XXX', $m1->a);
        $this->assertEquals('YYY', $m1->b);
        $this->assertEquals(null, $m1->c);

        $m2 = DIConstructorMockSAT::addTo($m, ['a'=>'XXX', 'John', 'b'=>'YYY']);
        $this->assertEquals('XXX', $m2->a);
        $this->assertEquals('YYY', $m2->b);
        $this->assertEquals('John', $m2->c);
    }
}
