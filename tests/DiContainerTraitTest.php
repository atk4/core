<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Phpunit\TestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class DiContainerTraitTest extends TestCase
{
    public function testFromSeed(): void
    {
        self::assertSame(StdSat::class, get_class(StdSat::fromSeed([StdSat::class])));
        self::assertSame(StdSat2::class, get_class(StdSat::fromSeed([StdSat2::class])));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Seed class is not a subtype of static class');
        StdSat2::fromSeed([StdSat::class]);
    }

    public function testNoPropExStandard(): void
    {
        $m = new FactoryDiMock2();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Property for specified object is not defined');
        $m->setDefaults(['not_exist' => 'qwerty']);
    }

    public function testNoPropExNumeric(): void
    {
        $m = new FactoryDiMock2();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Property for specified object is not defined');
        $m->setDefaults([5 => 'qwerty']); // @phpstan-ignore-line
    }

    public function testProperties(): void
    {
        $m = new FactoryDiMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar']);
        self::assertSame([$m->a, $m->b, $m->c], ['foo', 'BBB', 'bar']);

        $m = new FactoryDiMock2();
        $m->setDefaults(['a' => null, 'c' => false]);
        self::assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);

        $m = new FactoryDiMock2();
        $m->setDefaults(['typedNotNull' => 'x']);
        self::assertSame('x', $m->typedNotNull);
    }

    public function testPropertiesPassively(): void
    {
        $m = new FactoryDiMock2();

        $m->setDefaults(['a' => 'foo', 'c' => 'bar'], true);
        self::assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', 'bar']);

        $m = new FactoryDiMock2();
        $m->setDefaults(['a' => null, 'c' => false], true);
        self::assertSame([$m->a, $m->b, $m->c], ['AAA', 'BBB', false]);

        $m = new FactoryDiMock2();
        $m->a = ['foo'];
        $m->setDefaults(['a' => ['bar']], true);
        self::assertSame([$m->a, $m->b, $m->c], [['foo'], 'BBB', null]);

        $m = new FactoryDiMock2();
        $m->setDefaults(['typedNotNull' => 'x'], true);
        self::assertSame('x', $m->typedNotNull);
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testPassively(): void
    {
        $m = new FactoryDiMock2();
        $m->setDefaults([], true);
    }

    public function testInstanceOfBeforeConstructor(): void
    {
        $catchCalled = false;
        try {
            new FactoryDiMockConstructorMustNeverBeCalled();
        } catch (\Error $e) {
            $catchCalled = true;
        }
        self::assertTrue($catchCalled);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Seed class is not a subtype of static class');
        FactoryDiMockConstructorMustNeverBeCalled2::fromSeed([FactoryDiMockConstructorMustNeverBeCalled::class]);
    }
}

class FactoryDiMock2
{
    use DiContainerTrait;

    /** @var string|array<int, string> */
    public $a = 'AAA';
    /** @var string */
    public $b = 'BBB';
    /** @var string */
    public $c;

    public string $typedNotNull;
}

class FactoryDiMockConstructorMustNeverBeCalled
{
    public function __construct()
    {
        throw new \Error('Constructor must never be called');
    }
}

class FactoryDiMockConstructorMustNeverBeCalled2 extends FactoryDiMockConstructorMustNeverBeCalled
{
    use DiContainerTrait;
}
