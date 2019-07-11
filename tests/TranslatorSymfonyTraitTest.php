<?php


namespace atk4\core\tests;


use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\TranslatableTrait;
use atk4\core\TranslatorSymfonyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class TranslatorSymfonyTraitTest extends TestCase
{
    public function methodTranslate($excepted, $message, $params)
    {
        $app = new AppScopeTranslatorSymfonyMock();
        $mock = new TranslatableMock();

        $app->add($mock);
        $app->setTranslator(new TranslatorSymfony());

        $result = $mock->_($message, $params);
        $this->assertEquals($excepted,$result);
    }

    /**
    * @dataProvider getTransSimpleTest
    */
    public function testTransSimple($excepted, $message, $counter)
    {
        $this->methodTranslate($excepted, $message, $counter);
    }

    public function methodTranslatePlurals($excepted, $message, $counter)
    {
        $app = new AppScopeTranslatorSymfonyMock();
        $mock = new TranslatableMock();

        $app->add($mock);
        $app->setTranslator(new TranslatorSymfony());

        $result = $mock->_($message, ['%count%' => $counter]);
        $this->assertEquals($excepted,$result);
    }

    /**
     * @dataProvider getTransChoiceTests
     */
    public function testTransChoice($excepted, $message, $counter)
    {
        $this->methodTranslatePlurals($excepted, $message, $counter);
    }

    /**
     * @dataProvider getChooseTests
     */
    public function testTransChoose($excepted, $message, $counter)
    {
        $this->methodTranslatePlurals($excepted, $message, $counter);
    }


    public function getTransSimpleTest()
    {
        return [
            ['Symfony is great!', 'Symfony is great!', []],
            ['Symfony is awesome!', 'Symfony is %what%!', ['%what%' => 'awesome']],
            ['ATK4 version 2', 'ATK4 version %count%', ['%count%' => 2]],
        ];
    }

    public function getTransChoiceTests()
    {
        return [
            ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
            ['There are 0 apples', 'There is 1 apple|There are %count% apples', 0],
            ['There is 1 apple', 'There is 1 apple|There are %count% apples', 1],
            ['There are 10 apples', 'There is 1 apple|There are %count% apples', 10],
            // custom validation messages may be coded with a fixed value
            ['There are 2 apples', 'There are 2 apples', 2],
        ];
    }

    public function getChooseTests()
    {
        return [
            ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There are no apples', '{0}     There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There are no apples', '{0}There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],

            ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],

            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf]There are %count% apples', 10],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf]     There are %count% apples', 10],

            ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are 10 apples', 'There is one apple|There are %count% apples', 10],

            ['There are 0 apples', 'one: There is one apple|more: There are %count% apples', 0],
            ['There is one apple', 'one: There is one apple|more: There are %count% apples', 1],
            ['There are 10 apples', 'one: There is one apple|more: There are %count% apples', 10],

            ['There are no apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 0],
            ['There is one apple', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 1],
            ['There are 10 apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 10],

            ['', '{0}|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['', '{0} There are no apples|{1}|]1,Inf] There are %count% apples', 1],

            // Indexed only tests which are Gettext PoFile* compatible strings.
            ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are 2 apples', 'There is one apple|There are %count% apples', 2],

            // Tests for float numbers
            ['There is almost one apple', '{0} There are no apples|]0,1[ There is almost one apple|{1} There is one apple|[1,Inf] There is more than one apple', 0.7],
            ['There is one apple', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 1],
            ['There is more than one apple', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 1.7],
            ['There are no apples', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0],
            ['There are no apples', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0.0],
            ['There are no apples', '{0.0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0],

            // Test texts with new-lines
            // with double-quotes and \n in id & double-quotes and actual newlines in text
            ["This is a text with a\n            new-line in it. Selector = 0.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 0],
            // with double-quotes and \n in id and single-quotes and actual newlines in text
            ["This is a text with a\n            new-line in it. Selector = 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            ["This is a text with a\n            new-line in it. Selector > 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            // with double-quotes and id split accros lines
            ['This is a text with a
            new-line in it. Selector = 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            // with single-quotes and id split accros lines
            ['This is a text with a
            new-line in it. Selector > 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            // with single-quotes and \n in text
            ['This is a text with a\nnew-line in it. Selector = 0.', '{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.', 0],
            // with double-quotes and id split accros lines
            ["This is a text with a\nnew-line in it. Selector = 1.", "{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.", 1],
            // esacape pipe
            ['This is a text with | in it. Selector = 0.', '{0}This is a text with || in it. Selector = 0.|{1}This is a text with || in it. Selector = 1.', 0],
            // Empty plural set (2 plural forms) from a .PO file
            ['', '|', 1],
            // Empty plural set (3 plural forms) from a .PO file
            ['', '||', 1],
        ];
    }
}


class AppScopeTranslatorSymfonyMock extends AppScopeMock {
    use TranslatorSymfonyTrait;

    public function __construct()
    {
        $this->app = $this;
    }
};

class TranslatorSymfony implements TranslatorInterface {
    use TranslatorTrait;
}