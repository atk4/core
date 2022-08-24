<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception as ClassWithWarnDynamicPropertyTrait;
use Atk4\Core\Phpunit\TestCase;

class WarnDynamicPropertyTraitTest extends TestCase
{
    protected function runWithErrorConvertedToException(\Closure $fx): void
    {
        set_error_handler(function (int $errno, string $errstr) {
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
        $test = new class() extends ClassWithWarnDynamicPropertyTrait {
            public bool $p = false;
        };
        static::assertFalse($test->p);
        $test->p = true;
        static::assertTrue($test->p);
        unset($test->{'p'});

        $this->runWithErrorConvertedToException(function () use ($test) {
            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception@anonymous::$p');
            $test->p; // @phpstan-ignore-line
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
            static::assertTrue((new \ReflectionClass(get_parent_class($test)))->hasProperty('trace'));

            $this->expectException(WarnError::class);
            $this->expectErrorMessage('Undefined property: Atk4\Core\Exception::$trace');
            $test->trace; // @phpstan-ignore-line
        });
    }

    public function testGetSetWithWarningSuppressed(): void
    {
        $test = new class() extends ClassWithWarnDynamicPropertyTrait {
            public bool $p = false;
        };

        set_error_handler(function () {
            return true;
        });
        try {
            static::assertTrue($test->__isset('p'));
            static::assertFalse($test->p);
            static::assertFalse($test->__get('p'));
            $test->__set('p', true);
            static::assertTrue($test->p);
            static::assertTrue($test->__get('p'));
            $test->__unset('p');
            static::assertFalse($test->__isset('p'));
        } finally {
            restore_error_handler();
        }
    }
}

class WarnError extends \Exception
{
}
