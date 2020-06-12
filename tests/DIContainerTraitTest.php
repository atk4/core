<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\core\FactoryTrait;

/**
 * @coversDefaultClass \atk4\core\DIContainerTrait
 */
class DIContainerTraitTest extends AtkPhpunit\TestCase
{
    public function testFromSeed()
    {
        $this->assertSame(StdSAT::class, get_class(StdSAT::fromSeed([StdSAT::class])));
        $this->assertSame(StdSAT2::class, get_class(StdSAT::fromSeed([StdSAT2::class])));
        $this->assertSame(StdSAT2::class, get_class(StdSAT::fromSeed(StdSAT2::class)));

        $this->expectException(Exception::class);
        StdSAT2::fromSeed([StdSAT::class]);
    }

    public function testNoPropExNumeric()
    {
        $this->expectException(Exception::class);
        $m = new FactoryDIMock2();
        $m->setDefaults([5 => 'qwerty']);
    }

    public function testNoPropExStandard()
    {
        $this->expectException(Exception::class);
        $m = new FactoryDIMock2();
        $m->setDefaults(['not_exist' => 'qwerty']);
    }

    public function testProperties()
    {
        $m = new FactoryDIMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar']);
        $this->assertSame([$m->a, $m->b, $m->c], ['foo', 'BBB', 'bar']);

        $m = new FactoryDIMock2();
        $m->setDefaults(['a' => null, 'c' => false]);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);
    }

    public function testPropertiesPassively()
    {
        $m = new FactoryDIMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar'], true);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', 'bar']);

        $m = new FactoryDIMock2();
        $m->setDefaults(['a' => null, 'c' => false], true);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);

        $m = new FactoryDIMock2();
        $m->a = ['foo'];
        $m->setDefaults(['a' => ['bar']], true);
        $this->assertSame([$m->a, $m->b, $m->c], [['foo'], 'BBB', null]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testPassively()
    {
        $m = new FactoryDIMock2();
        $m->setDefaults([], true);
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
