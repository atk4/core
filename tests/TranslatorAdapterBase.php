<?php

namespace atk4\core\tests;

use PHPUnit\Framework\TestCase;

abstract class TranslatorAdapterBase extends TestCase
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
        'Unable to determine persistence driver from DSN'                        => 'Unable to determine persistence driver from DSN',
        'Unable to serialize field value on load'                                => 'Unable to serialize field value on load ({{field}})',
        'Unable to serialize field value on save'                                => 'Unable to serialize field value on save ({{field}})',
        'Unable to typecast field value on load'                                 => 'Unable to typecast field value on load ({{field}})',
        'Unable to typecast field value on save'                                 => 'Unable to typecast field value on save ({{field}})',
    ];
    */

    public function testBase()
    {
        $message = 'Field requires array for defaults';

        $actual = $this->translate($message, [], 'atk', 'en');
        $this->assertEquals($message, $actual);
    }

    public function testSubstitution()
    {
        $message = 'Unable to serialize field value on load';

        $actual = $this->translate($message, ['field' => 'field_name'], 'atk', 'en');
        $this->assertEquals('Unable to serialize field value on load (field_name)', $actual);
    }

    public function testPluralZero()
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 0], 'atk', 'it');
        $this->assertEquals('Test zero', $actual);
    }

    public function testPluralBig()
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 50], 'atk', 'it');
        $this->assertEquals('Test sono 50', $actual);
    }

    public function testPluralOne()
    {
        $message = 'Test with plural';

        $actual = $this->translate($message, ['count' => 1], 'atk', 'it');
        $this->assertEquals('Test è uno', $actual);
    }
}
