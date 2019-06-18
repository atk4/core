<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\TranslatableTrait;
use atk4\core\Translator;

class TranslatableTraitTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatableTraitMock */
    public $mock;

    public function setUp()
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
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input    input string to test
     * @param $output   excepted output
     */
    public function testMethodWithCounter_($input, $output)
    {
        $trans = $this->mock->_($input, 0);
        $this->assertEquals($output, $trans);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input    input string to test
     * @param $output   excepted output
     */
    public function testMethod_d($input, $output)
    {
        $trans = $this->mock->_d('atk4', $input);
        $this->assertEquals($output, $trans);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input    input string to test
     * @param $output   excepted output
     */
    public function testMethodWithCounter_d($input, $output)
    {
        $trans = $this->mock->_d('atk4', $input, 0);
        $this->assertEquals($output, $trans);
    }
}


class TranslatableTraitMock {
    use TranslatableTrait;
}