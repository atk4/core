<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Translator;

use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\Translator\Adapter\Generic;
use Atk4\Core\Translator\Translator;

abstract class AdapterBaseTest extends TestCase
{
    abstract public function getTranslatableMock(): object;

    protected function setUp(): void
    {
        parent::setUp();

        $adapter = new Generic();
        $adapter->addDefinitionFromArray([
            'Field requires array for defaults' => 'Field requires array for defaults',
            'Unable to serialize field value on load' => 'Unable to serialize field value on load ({{field}})',
        ], 'en', 'atk');
        $adapter->addDefinitionFromArray([
            'Field requires array for defaults' => 'Il campo richiede un array per i valori predefiniti',
            'Test with plural' => [
                'zero' => 'Test zero',
                'one' => 'Test è uno',
                'other' => 'Test sono {{count}}',
            ],
        ], 'it', 'atk');
        Translator::instance()->setAdapter($adapter);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function translate(string $message, array $parameters, string $context, string $locale): string
    {
        $mock = $this->getTranslatableMock();

        return $mock->_($message, $parameters, $context, $locale);
    }

    public function testBase(): void
    {
        $message = 'Field requires array for defaults';

        $actual = $this->translate($message, [], 'atk', 'en');
        self::assertSame($message, $actual);

        $actual = $this->translate($message, [], 'atk', 'it');
        self::assertSame('Il campo richiede un array per i valori predefiniti', $actual);
    }

    public function testSubstitution(): void
    {
        $message = 'Unable to serialize field value on load';

        $actual = $this->translate($message, ['field' => 'field_name'], 'atk', 'en');
        self::assertSame('Unable to serialize field value on load (field_name)', $actual);
    }

    public function testPluralZero(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 0], 'atk', 'it');
        self::assertSame('Test zero', $actual);
    }

    public function testPluralBig(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 50], 'atk', 'it');
        self::assertSame('Test sono 50', $actual);
    }

    public function testPluralOne(): void
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 1], 'atk', 'it');
        self::assertSame('Test è uno', $actual);
    }
}
