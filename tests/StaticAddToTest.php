<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\StaticAddToTrait;
use Atk4\Core\TrackableTrait;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class StdSat extends \stdClass
{
    use StaticAddToTrait;
}

class StdSat2 extends StdSat
{
    public function foo(): void {}
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
        $this->expectExceptionMessage('Object is not an instance of static class');
        StdSat2::assertInstanceOf(new StdSat());
    }

    private function createStdSat2AsParent(): \stdClass
    {
        return new StdSat2();
    }

    private function createStdSat2AsNullable(): ?StdSat2 // @phpstan-ignore return.unusedType
    {
        return new StdSat2();
    }

    /**
     * @return StdSat2|false
     */
    private function createStdSat2AsUnionWithFalse() // @phpstan-ignore return.unusedType
    {
        return new StdSat2();
    }

    /**
     * @return mixed
     */
    private function createStdSat2AsMixed()
    {
        return new StdSat2();
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testAssertInstanceOfPhpstan(): void
    {
        $o = $this->createStdSat2AsParent();
        $o->foo(); // @phpstan-ignore method.nonObject

        $o = $this->createStdSat2AsParent();
        StdSat2::assertInstanceOf($o)->foo();

        $o = new StdSat2();
        StdSat::assertInstanceOf($o)->foo();

        $o = $this->createStdSat2AsNullable();
        StdSat2::assertInstanceOf($o)->foo();

        $o = $this->createStdSat2AsUnionWithFalse();
        StdSat2::assertInstanceOf($o)->foo();

        $o = $this->createStdSat2AsMixed();
        StdSat2::assertInstanceOf($o)->foo();

        $o = $this->createStdSat2AsParent();
        StdSat2::assertInstanceOf($o);
        $o->foo(); // @phpstan-ignore method.nonObject (TODO remove once https://github.com/phpstan/phpstan/issues/12548 is fixed)
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
