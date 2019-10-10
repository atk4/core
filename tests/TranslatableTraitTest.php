<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\TranslatableTrait;
use PHPUnit\Framework\TestCase;

class TranslatableTraitTest extends TestCase
{
    /**
     * Basic test for usage in ATK without external translator.
     *
     * @dataProvider getTransSimpleTest
     */
    public function testTranslatableTrait($excepted, $message, $parameters)
    {
        $mock = new TranslatableMock();

        $result = $mock->_($message, $parameters);
        $this->assertEquals($excepted, $result);
    }

    /**
     * Basic test for usage in ATK with App without external translator.
     *
     * @dataProvider getTransSimpleTest
     */
    public function testTranslatableTraitWithApp($excepted, $message, $parameters)
    {
        $app = new AppScopeMock();
        $app->app = $app;
        $mock = new TranslatableMock();

        $app->add($mock);

        $result = $mock->_($message, $parameters);
        $this->assertEquals($excepted, $result);
    }

    public function getTransSimpleTest()
    {
        return [
            // simple string to string
            ['ATK4 is great!', 'ATK4 is great!', []],
            // simple substitution
            ['ATK4 is awesome!', 'ATK4 is %what%!', ['%what%' => 'awesome']],
            ['There is one apple', 'There is one apple', ['%count%' => 1]], // just to cover a specific case
            // PoFile compatible with counter format : one|more
            ['There are 0 apples', 'There is one apple|There are %count% apples', ['%count%' => 0]],
            ['There is one apple', 'There is one apple|There are %count% apples', ['%count%' => 1]],
            ['There are 2 apples', 'There is one apple|There are %count% apples', ['%count%' => 2]],
        ];
    }
}

class TranslatableMock
{
    use AppScopeTrait;
    use TranslatableTrait;
}
