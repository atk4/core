<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\AtkPhpunit;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\core\FactoryTrait;
use atk4\core\HookBreaker as HB;

/**
 * @coversDefaultClass \atk4\core\FactoryTrait
 */
class FactoryTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test factory().
     */
    public function testFactory()
    {
        $m = new FactoryMock();

        // pass object
        $m1 = new FactoryMock();
        $m2 = $m->factory($m1);
        $this->assertSame($m1, $m2);

        // pass classname
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $this->assertSame(FactoryMock::class, get_class($m1));
    }

    /**
     * Object factory definition must use ["class name", "x"=>"y"] form.
     */
    public function testException1()
    {
        // wrong 1st parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m->factory(['wrong_parameter' => 'qwerty']);
    }

    /**
     * Test factory parameters.
     */
    public function testParameters()
    {
        $m = new FactoryDIMock();

        // as class name
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $this->assertSame(FactoryMock::class, get_class($m1));

        $m1 = $m->factory(\atk4\core\tests\FactoryMock::class);
        $this->assertSame(FactoryMock::class, get_class($m1));

        $m1 = $m->factory(FactoryMock::class);
        $this->assertSame(FactoryMock::class, get_class($m1));

        $m1 = $m->factory(HB::class, ['ok']);
        $this->assertSame(\atk4\core\HookBreaker::class, get_class($m1));

        $m1 = $m->factory(['atk4\core\tests\FactoryMock']);
        $this->assertSame(FactoryMock::class, get_class($m1));

        // as object
        $m2 = $m->factory($m1);
        $this->assertSame(FactoryMock::class, get_class($m2));

        $m2 = $m->factory([$m1]);
        $this->assertSame(FactoryMock::class, get_class($m2));

        // as class name with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertNull($m1->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertSame('AAA', $m1->a);
        $this->assertSame('YYY', $m1->b);
        $this->assertSame('ZZZ', $m1->c);

        // as object with parameters
        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => 'YYY']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertNull($m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock');
        $m2 = $m->factory($m1, ['a' => null, 'b' => 'YYY', 'c' => 'ZZZ']);
        $this->assertSame('AAA', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('ZZZ', $m2->c);

        $m1 = $m->factory('atk4\core\tests\FactoryDIMock', ['a' => null, 'b' => 'YYY', 'c' => 'SSS']);
        $m2 = $m->factory($m1, ['a' => 'XXX', 'b' => null, 'c' => 'ZZZ']);
        $this->assertSame('XXX', $m2->a);
        $this->assertSame('YYY', $m2->b);
        $this->assertSame('ZZZ', $m2->c);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as class name.
     */
    public function testParametersException1()
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock', ['not_exist' => 'test']);
    }

    /**
     * Object factory can not add not defined properties.
     * Receive as object.
     */
    public function testParametersException2()
    {
        // wrong property in 2nd parameter
        $this->expectException(Exception::class);
        $m = new FactoryMock();
        $m1 = $m->factory('atk4\core\tests\FactoryMock');
        $m2 = $m->factory($m1, ['not_exist' => 'test']);
    }
}

// @codingStandardsIgnoreStart
class FactoryMock
{
    use FactoryTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
class FactoryDIMock
{
    use FactoryTrait;
    use DIContainerTrait;

    public $a = 'AAA';
    public $b = 'BBB';
    public $c;
}
class FactoryAppScopeMock
{
    use AppScopeTrait;
    use FactoryTrait;
}
// @codingStandardsIgnoreEnd
