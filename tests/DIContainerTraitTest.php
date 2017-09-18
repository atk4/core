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
     * Ignore numeric property names (array keys).
     */
    public function testException1()
    {
        $m = new FactoryDIMock2();
        $m->setDefaults([5 => 'qwerty']);
    }

    /**
     * Do not allow non existant property names (array keys).
     *
     * @expectedException     Exception
     */
    public function testException2()
    {
        $m = new FactoryDIMock2();
        $m->setDefaults(['not_exist' => 'qwerty']);
    }

    /**
     * Test properties.
     */
    public function testProperties()
    {
        $m = new FactoryDIMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar']);
        $this->assertEquals([$m->a, $m->b, $m->c], ['foo', 'BBB', 'bar']);

        $m = new FactoryDIMock2();
        $m->setDefaults(['a' => null, 'c' => false]);
        $this->assertEquals([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);
    }

    /**
     * Test properties.
     */
    public function testPropertiesPassively()
    {
        $m = new FactoryDIMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar'], true);
        $this->assertEquals([$m->a, $m->b, $m->c], ['AAA', 'BBB', 'bar']);

        $m = new FactoryDIMock2();
        $m->setDefaults(['a' => null, 'c' => false], true);
        $this->assertEquals([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);

        $m = new FactoryDIMock2();
        $m->a = ['foo'];
        $m->setDefaults(['a' => ['bar']], true);
        $this->assertEquals([$m->a, $m->b, $m->c], [['foo'], 'BBB', null]);
    }

    public function testPassively()
    {
        $m = new FactoryDIMock2();
        $m->setDefaults(null, true);
    }
}

// @codingStandardsIgnoreStart
class FactoryDIMock2
{
    use FactoryTrait;
    use DIContainerTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
// @codingStandardsIgnoreEnd
