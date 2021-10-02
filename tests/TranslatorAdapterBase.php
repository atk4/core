<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\Phpunit\TestCase;

abstract class TranslatorAdapterBase extends TestCase
{
    abstract public function getTranslatableMock(): object;

    public function translate(string $message, array $params, string $context, string $locale): string
    {
        $mock = $this->getTranslatableMock();

        return $mock->_($message, $params, $context, $locale);
    }

    public function testBase(): void
    {
        $message = 'Field requires array for defaults';

        $actual = $this->translate($message, [], 'atk', 'en');
        $this->assertSame($message, $actual);
    }

    public function testSubstitution(): void
    {
        $message = 'Unable to serialize field value on load';

        $actual = $this->translate($message, ['field' => 'field_name'], 'atk', 'en');
        $this->assertSame('Unable to serialize field value on load (field_name)', $actual);
    }

    public function testPluralZero(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 0], 'atk', 'it');
        $this->assertSame('Test zero', $actual);
    }

    public function testPluralBig(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 50], 'atk', 'it');
        $this->assertSame('Test sono 50', $actual);
    }

    public function testPluralOne(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 1], 'atk', 'it');
        $this->assertSame('Test è uno', $actual);
    }
}
