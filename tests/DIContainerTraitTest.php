<?php

namespace atk4\core\tests;

use atk4\core\DIContainerTrait;
use atk4\core\FactoryTrait;

/**
 * @coversDefaultClass \atk4\core\DIContainerTrait
 */
class DIContainerTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Do not allow numeric property names (array keys).
     *
     * @expectedException     Exception
     */
    public function testException1()
    {
        $m = new FactoryDIMock();
        $m->setDefaults([5 => 'qwerty']);
    }

    /**
     * Do not allow non existant property names (array keys).
     *
     * @expectedException     Exception
     */
    public function testException2()
    {
        $m = new FactoryDIMock();
        $m->setDefaults(['not_exist' => 'qwerty']);
    }

    /**
     * Test properties.
     */
    public function testProperties()
    {
        $m = new FactoryDIMock();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar']);
        $this->assertEquals([$m->a, $m->b, $m->c], ['foo', 'BBB', 'bar']);

        $m->setDefaults(['a' => null, 'c' => false]);
        $this->assertEquals([$m->a, $m->b, $m->c], ['foo', 'BBB', false]);
    }
}

// @codingStandardsIgnoreStart
class FactoryDIMock
{
    use FactoryTrait;
    use DIContainerTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
// @codingStandardsIgnoreEnd
