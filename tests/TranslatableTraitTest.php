<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\TranslatableTrait;
use atk4\core\Translator;
use PHPUnit\Framework\TestCase;

class TranslatableTraitTest extends TestCase
{
    /** @var TranslatableTraitMock */
    public $mock;

    public function setUp() :void
    {
        $this->mock = new TranslatableTraitMock();
    }

    public function dataProvider()
    {
        return [['string to test','string to test']];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input    input string to test
     * @param $output   excepted output
     */
    public function testMethod_($input, $output)
    {
        $trans = $this->mock->_($input);
        $this->assertEquals($output, $trans);

        $trans = $this->mock->_($input,[]);
        $this->assertEquals($output, $trans);

        $trans = $this->mock->_($input,[],$input);
        $this->assertEquals($output, $trans);
    }
}

// @codingStandardsIgnoreStart
class TranslatableTraitMock {
    use TranslatableTrait;
}
// @codingStandardsIgnoreEnd