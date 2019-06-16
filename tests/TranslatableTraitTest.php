<?php

namespace atk4\core\tests;

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
        $trans = $this->translatableMock->_('string without counter');
        $this->assertEquals('string without counter translated', $trans);
    }

    public function testSingular()
    {
        $trans = $this->translatableMock->_('string not translated simple');
        $this->assertEquals('string translated', $trans);
    }

    public function testSingularNotExists()
    {
        $trans = $this->translatableMock->_('string not exists');
        $this->assertEquals('string not exists', $trans);
    }

    public function testPlurals0()
    {
        $trans = $this->translatableMock->_('string not translated with plurals', 0);
        $this->assertEquals('string translated zero', $trans);
    }

    public function testPlurals1()
    {
        $trans = $this->translatableMock->_('string not translated with plurals', 1);
        $this->assertEquals('string translated singular', $trans);
    }

    public function testPlurals2()
    {
        $trans = $this->translatableMock->_('string not translated with plurals', 2);
        $this->assertEquals('string translated plural', $trans);
    }

    public function testPluralsBiggerThanMaxPlurals()
    {
        $trans = $this->translatableMock->_('string not translated with plurals', 300);
        $this->assertEquals('string translated plural', $trans);
    }

    // EXCEPTION
    public function testTranslationKeyPresentButEmpty()
    {
        $this->expectException(\atk4\core\Exception::class);
        $trans = $this->translatableMock->_('string with exception', 2);
    }
}
