<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception as ClassWithWarnDynamicPropertyTrait;
use Atk4\Core\Phpunit\TestCase;

class WarnDynamicPropertyTraitTest extends TestCase
{
    protected function runWithErrorConvertedToException(\Closure $fx): void
    {
        set_error_handler(function (int $errno, string $errstr): void {
            throw new WarnError($errstr);
        });
        try {
            $fx();
        } finally {
            restore_error_handler();
        }
    }

    public function testIssetException(): void
    {
        $this->runWithErrorConvertedToException(fn () => null);
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$xxx');
            isset($test->xxx); // @phpstan-ignore-line
        });
    }

    public function testGetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$xxx');
            $test->xxx; // @phpstan-ignore-line
        });
    }

    public function testSetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$xxx');
            $test->xxx = 5; // @phpstan-ignore-line
        });
    }

    public function testUnsetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$xxx');
            unset($test->{'xxx'});
        });
    }

    public function testGetSetPublicProperty(): void
    {
        $test = new ClassWithWarnDynamicPropertyTrait();
        $this->assertTrue(isset($test->params)); // @phpstan-ignore-line
        $test->params = ['foo'];
        $this->assertSame(['foo'], $test->params);
        unset($test->{'params'});

        $this->runWithErrorConvertedToException(function () use ($test) {
            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$params');
            $test->params; // @phpstan-ignore-line
        });
    }

    public function testGetProtectedPropertyException(): void
    {
        $test = new ClassWithWarnDynamicPropertyTrait();

        $this->expectException(\Error::class);
        $this->expectErrorMessage('Cannot access protected property Atk4\Core\Exception::$customExceptionTitle');
        $test->customExceptionTitle; // @phpstan-ignore-line
    }

    public function testGetPrivateProperty(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$trace2');
            $test->trace2; // @phpstan-ignore-line
        });
    }
}

class WarnError extends \Exception
{
}
