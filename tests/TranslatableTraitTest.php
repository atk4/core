<?php

namespace atk4\core\tests;


use atk4\core\Exception;
use atk4\core\TranslatableTrait;
use atk4\core\TranslatorInterface;

class TranslatableTraitTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatableMock */
    public $translatableMock;

    public function setUp()
    {
        $this->translatableMock = new TranslatableMock();
    }

    public function testStraight()
    {
        $trans = $this->translatableMock->__('string without counter');
        $this->assertEquals('string without counter translated',$trans);
    }

    public function testSingular()
    {
        $trans = $this->translatableMock->__('string not translated simple');
        $this->assertEquals('string translated',$trans);
    }

    public function testSingularNotExists()
    {
        $trans = $this->translatableMock->__('string not exists');
        $this->assertEquals('string not exists',$trans);
    }

    public function testPlurals0()
    {
        $trans = $this->translatableMock->__('string not translated with plurals',0);
        $this->assertEquals('string translated zero',$trans);
    }

    public function testPlurals1()
    {
        $trans = $this->translatableMock->__('string not translated with plurals',1);
        $this->assertEquals('string translated singular',$trans);
    }

    public function testPlurals2()
    {
        $trans = $this->translatableMock->__('string not translated with plurals',2);
        $this->assertEquals('string translated plural',$trans);
    }

    public function testPluralsBiggerThanMaxPlurals()
    {
        $trans = $this->translatableMock->__('string not translated with plurals',300);
        $this->assertEquals('string translated plural',$trans);
    }

    // EXCEPTION
    public function testTranslationKeyPresentButEmpty()
    {
        $this->expectException(\atk4\core\Exception::class);
        $trans = $this->translatableMock->__('string with exception',2);
    }

    // SPRINTF

    public function testStringSprintf()
    {
        $trans = $this->translatableMock->__('single: %s, zero: %s, singular : %s, plural : %s',
            'string without counter',
            ['string translated',1],
            ['string not translated with plurals',0],
            ['string not translated with plurals',1],
            ['string not translated with plurals',2]
        );

        $this->assertEquals('translated : zero: string without counter translated, singular : string translated, plural : string translated singular',$trans);
    }
}
