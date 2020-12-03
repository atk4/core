<?php

declare(strict_types=1);

namespace atk4\core\Tests;

use atk4\core\AtkPhpunit;
use atk4\core\DiContainerTrait;
use atk4\core\Exception;

/**
 * @coversDefaultClass \atk4\core\DiContainerTrait
 */
class DiContainerTraitTest extends AtkPhpunit\TestCase
{
    public function testFromSeed()
    {
        $this->assertSame(StdSat::class, get_class(StdSat::fromSeed([StdSat::class])));
        $this->assertSame(StdSat2::class, get_class(StdSat::fromSeed([StdSat2::class])));

        $this->expectException(Exception::class);
        StdSat2::fromSeed([StdSat::class]);
    }

    public function testNoPropExNumeric()
    {
        $this->expectException(\Error::class);
        $m = new FactoryDiMock2();
        $m->setDefaults([5 => 'qwerty']);
    }

    public function testNoPropExStandard()
    {
        $this->expectException(Exception::class);
        $m = new FactoryDiMock2();
        $m->setDefaults(['not_exist' => 'qwerty']);
    }

    public function testProperties()
    {
        $m = new FactoryDiMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar']);
        $this->assertSame([$m->a, $m->b, $m->c], ['foo', 'BBB', 'bar']);

        $m = new FactoryDiMock2();
        $m->setDefaults(['a' => null, 'c' => false]);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);
    }

    public function testPropertiesPassively()
    {
        $m = new FactoryDiMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar'], true);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', 'bar']);

        $m = new FactoryDiMock2();
        $m->setDefaults(['a' => null, 'c' => false], true);
        $this->assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);

        $m = new FactoryDiMock2();
        $m->a = ['foo'];
        $m->setDefaults(['a' => ['bar']], true);
        $this->assertSame([$m->a, $m->b, $m->c], [['foo'], 'BBB', null]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testPassively()
    {
        $m = new FactoryDiMock2();
        $m->setDefaults([], true);
    }
}

// @codingStandardsIgnoreStart
class FactoryDiMock2
{
    use DiContainerTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
// @codingStandardsIgnoreEnd
