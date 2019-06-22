<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\TranslatableTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class TranslatableTraitTest extends TestCase
{
    private function getTranslatorMock()
    {
        return new class() implements TranslatorInterface {
            use TranslatorTrait;
        };
    }

    private function getMockCaseApp($translator = null)
    {
        $app = new class($translator) {
            use AppScopeTrait;
            use ContainerTrait;
            use TranslatableTrait {
                hasTranslatorInAppScope as public;
                hasTranslator as public;
            }

            public function __construct($translator)
            {
                $this->app = $this;

                if (null !== $translator) {
                    $this->setTranslator($translator);
                }
            }
        };

        return $app;
    }

    private function getMockCaseAppChild($translator = null)
    {
        $app = $this->getMockCaseApp($translator);

        $child = new class() {
            use AppScopeTrait;
            use TranslatableTrait {
                hasTranslatorInAppScope as public;
                hasTranslator as public;
            }
        };

        $app->add($child);

        return $child;
    }

    private function getMockCaseNoATK($translator = null)
    {
        return new class($translator) {
            use TranslatableTrait {
                hasTranslatorInAppScope as public;
                hasTranslator as public;
            }

            public function __construct($translator)
            {
                if (null !== $translator) {
                    $this->setTranslator($translator);
                }
            }
        };
    }

    /**
     * @dataProvider getTransTests
     */
    public function testSetTranslator()
    {
        $mock = new class() {
            use TranslatableTrait;
        };

        $mock->setTranslator($this->getTranslatorMock());
        $this->assertTrue(is_a($mock->getTranslator(), TranslatorInterface::class));
    }

    /**
     * Test that Translation will be delegated to the right object
     */
    public function testConditionalBehaviourWithoutTranslator()
    {
        // WITHOUT Translator :: use internal implementation

        /**
         * App without Translator
         */
        $mock = $this->getMockCaseApp();
        // App will not use own translator
        $this->assertFalse($mock->hasTranslator());
        // App not use translator from AppScope, App = AppScope
        $this->assertFalse($mock->hasTranslatorInAppScope());

        /**
         * Child of AppScope without Translator
         */
        $mock = $this->getMockCaseAppChild();
        // Child will not use own Translator
        $this->assertFalse($mock->hasTranslator());
        // Child will use AppScope Translator
        $this->assertTrue($mock->hasTranslatorInAppScope());
        // App will not use AppScope Translator
        $this->assertFalse($mock->app->hasTranslatorInAppScope());
        // App will not use own Translator
        $this->assertFalse($mock->app->hasTranslator());

        /**
         * Standalone implementation without Translator
         */
        $mock = $this->getMockCaseNoATK();
        // Object without Translator
        $this->assertFalse($mock->hasTranslator());
        // Object without AppScope Translator
        $this->assertFalse($mock->hasTranslatorInAppScope());
    }

    /**
     * Test that Translation will be delegated to the right object
     */
    public function testConditionalBehaviourWithTranslator()
    {
        /**
         * App with Translator
         */
        $mock = $this->getMockCaseApp($this->getTranslatorMock());
        // App will use own Translator
        $this->assertTrue($mock->hasTranslator());
        // App not use Translator from AppScope, App = AppScope
        $this->assertFalse($mock->hasTranslatorInAppScope());

        /**
         * Child of AppScope with Translator
         */
        $mock = $this->getMockCaseAppChild($this->getTranslatorMock());
        // Child will not use own Translator
        $this->assertFalse($mock->hasTranslator());
        // Child will use AppScope Translator
        $this->assertTrue($mock->hasTranslatorInAppScope());
        // App will not use AppScope Translator
        $this->assertFalse($mock->app->hasTranslatorInAppScope());
        // App will use own Translator
        $this->assertTrue($mock->app->hasTranslator());

        /**
         * Standalone implementation with Translator
         */
        $mock = $this->getMockCaseNoATK($this->getTranslatorMock());
        // Child will use own Translator
        $this->assertTrue($mock->hasTranslator());
        // Child will not use AppScope Translator
        $this->assertFalse($mock->hasTranslatorInAppScope());
    }

    /**
     * @dataProvider getTransTests
     *
     * @param $expected
     * @param $id
     * @param $parameters
     */
    public function testTrans($expected, $id, $parameters)
    {
        $app = $this->getMockCaseApp();
        $this->assertEquals($expected, $app->_($id, $parameters));

        $app = $this->getMockCaseAppChild();
        $this->assertEquals($expected, $app->_($id, $parameters));

        $app = $this->getMockCaseNoATK();
        $this->assertEquals($expected, $app->_($id, $parameters));

        // with Translator

        $app = $this->getMockCaseApp($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, $parameters));

        $app = $this->getMockCaseAppChild($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, $parameters));

        $app = $this->getMockCaseNoATK($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, $parameters));
    }

    /**
     * @dataProvider getChooseTests
     *
     * @param $expected
     * @param $id
     * @param $number
     */
    public function testChoose($expected, $id, $number)
    {
        $app = $this->getMockCaseApp();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseAppChild();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseNoATK();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        // with Translator

        $app = $this->getMockCaseApp($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseAppChild($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseNoATK($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));
    }

    /**
     * @dataProvider getTransChoiceTests
     *
     * @param $expected
     * @param $id
     * @param $number
     */
    public function testTransChoice($expected, $id, $number)
    {
        $app = $this->getMockCaseApp();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseAppChild();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseNoATK();
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        // with Translator

        $app = $this->getMockCaseApp($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseAppChild($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));

        $app = $this->getMockCaseNoATK($this->getTranslatorMock());
        $this->assertEquals($expected, $app->_($id, ['%count%' => $number]));
    }

    /**
     * @dataProvider getNonMatchingMessages
     *
     * @param $id
     * @param $number
     */
    public function testThrowExceptionIfMatchingMessageCannotBeFound1($id, $number)
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = $this->getMockCaseApp();
        $this->assertEquals($id, $app->_($id, ['%count%' => $number]));
    }

    /**
     * @dataProvider getNonMatchingMessages
     *
     * @param $id
     * @param $number
     */
    public function testThrowExceptionIfMatchingMessageCannotBeFound2($id, $number)
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = $this->getMockCaseAppChild();
        $this->assertEquals($id, $app->_($id, ['%count%' => $number]));
    }

    /**
     * @dataProvider getNonMatchingMessages
     *
     * @param $id
     * @param $number
     */
    public function testThrowExceptionIfMatchingMessageCannotBeFound3($id, $number)
    {
        $this->expectException(\InvalidArgumentException::class);
        $app = $this->getMockCaseNoATK();
        $this->assertEquals($id, $app->_($id, ['%count%' => $number]));
    }

    // Data Providers FROM Symfony Translator Test

    public function getTransTests()
    {
        return [
            ['Symfony is great!', 'Symfony is great!', []],
            ['Symfony is awesome!', 'Symfony is %what%!', ['%what%' => 'awesome']],
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

    public function getNonMatchingMessages()
    {
        return [
            ['{0} There are no apples|{1} There is one apple', 2],
            ['{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['{1} There is one apple|]2,Inf] There are %count% apples', 2],
            ['{0} There are no apples|There is one apple', 2],
        ];
    }
}
