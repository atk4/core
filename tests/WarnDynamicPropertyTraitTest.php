<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Exception as ClassWithWarnDynamicPropertyTrait;
use Atk4\Core\Phpunit\TestCase;

class WarnDynamicPropertyTraitTest extends TestCase
{
    /**
     * @param \Closure(): void $fx
     */
    protected function runWithErrorConvertedToException(\Closure $fx): void
    {
        set_error_handler(static function (int $errno, string $errstr) {
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
        $this->runWithErrorConvertedToException(static function () {});
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception::$xxx');
            isset($test->xxx); // @phpstan-ignore property.notFound, expr.resultUnused
        });
    }

    public function testGetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception::$xxx');
            $test->xxx; // @phpstan-ignore property.notFound, expr.resultUnused
        });
    }

    public function testSetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception::$xxx');
            $test->xxx = 5; // @phpstan-ignore property.notFound
        });
    }

    public function testUnsetException(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();

            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception::$xxx');
            unset($test->{'xxx'}); // @phpstan-ignore property.notFound
        });
    }

    public function testGetSetPublicProperty(): void
    {
        $test = new class() extends ClassWithWarnDynamicPropertyTrait {
            public bool $p = false;
        };
        self::assertFalse($test->p);
        $test->p = true;
        self::assertTrue($test->p);
        unset($test->{'p'});

        $this->runWithErrorConvertedToException(function () use ($test) {
            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception@anonymous::$p');
            $test->p; // @phpstan-ignore expr.resultUnused
        });
    }

    public function testGetProtectedPropertyException(): void
    {
        $test = new ClassWithWarnDynamicPropertyTrait();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot access protected property Atk4\Core\Exception::$customExceptionTitle');
        $test->customExceptionTitle; // @phpstan-ignore property.protected, expr.resultUnused
    }

    public function testGetPrivateProperty(): void
    {
        $this->runWithErrorConvertedToException(function () {
            $test = new ClassWithWarnDynamicPropertyTrait();
            self::assertTrue((new \ReflectionClass(get_parent_class($test)))->hasProperty('trace'));

            $this->expectException(WarnError::class);
            $this->expectExceptionMessage('Undefined property: Atk4\Core\Exception::$trace');
            $test->trace; // @phpstan-ignore property.notFound, expr.resultUnused
        });
    }

    public function testGetSetWithWarningSuppressed(): void
    {
        $test = new class() extends ClassWithWarnDynamicPropertyTrait {
            public bool $p = false;
        };

        set_error_handler(static function () {
            return true;
        });
        try {
            self::assertTrue($test->__isset('p'));
            self::assertFalse($test->p);
            self::assertFalse($test->__get('p'));
            $test->__set('p', true);
            self::assertTrue($test->p);
            self::assertTrue($test->__get('p'));
            $test->__unset('p');
            self::assertFalse($test->__isset('p'));
        } finally {
            restore_error_handler();
        }
    }
}

class WarnError extends \ErrorException {}
