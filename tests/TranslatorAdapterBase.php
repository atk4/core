<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AtkPhpunit;

abstract class TranslatorAdapterBase extends AtkPhpunit\TestCase
{
    abstract public function getTranslatableMock();

    public function translate($message, $params, $context, $locale)
    {
        $mock = $this->getTranslatableMock();

        return $mock->_($message, $params, $context, $locale);
    }

    /* DEFINITIONS
    [
        'Field requires array for defaults'                                      => 'Field requires array for defaults',
        'Field value can not be base64 encoded because it is not of string type' => 'Field value can not be base64 encoded because it is not of string type ({{field}})',
        'Mandatory field value cannot be null'                                   => 'Mandatory field value cannot be null ({{field}})',
        'Model is already related to another persistence'                        => 'Model is already related to another persistence',
        'Must not be null'                                                       => 'Must not be null',
        'Test with plural'                                                       => [
            'zero'  => 'Test zero',
            'one'   => 'Test is one',
            'other' => 'Test are {{count}}',
        ],
        'There was error while decoding JSON'                                    => 'There was error while decoding JSON',
        'Unable to determine persistence driver type from DSN'                   => 'Unable to determine persistence driver type from DSN',
        'Unable to serialize field value on load'                                => 'Unable to serialize field value on load ({{field}})',
        'Unable to serialize field value on save'                                => 'Unable to serialize field value on save ({{field}})',
        'Unable to typecast field value on load'                                 => 'Unable to typecast field value on load ({{field}})',
        'Unable to typecast field value on save'                                 => 'Unable to typecast field value on save ({{field}})',
    ];
    */

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
