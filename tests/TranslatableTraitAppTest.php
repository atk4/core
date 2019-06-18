<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\PHPUnit7_AgileTestCase;
use atk4\core\TranslatableTrait;
use atk4\core\Translator;

class TranslatableTraitAppTest extends \PHPUnit_Framework_TestCase
{
    public $appMock;
    public $translatableChildMock;
    public $skip_case_string_empty = true;

    public static $translations_runtime_add = [
        'string without counter'       => ['string without counter translated'],
        'string not translated simple' => [
            1 => 'string translated',
        ],
        'string not translated with plurals' => [
            0 => 'string translated zero',
            1 => 'string translated singular',
            2 => 'string translated plural',
        ],
        'string with exception array empty'                            => []
    ];

    public function objectTranslatorProvider()
    {
        return [
            [$this->getTransalatableChild($this->getTranslatorForRuntimeAdd())],

            // with no fallback language
            [$this->getTransalatableChild($this->getTranslatorForConfigPHPInline(false))],
            [$this->getTransalatableChild($this->getTranslatorForConfigPHP(false))],
            [$this->getTransalatableChild($this->getTranslatorForConfigJSON(false))],
            [$this->getTransalatableChild($this->getTranslatorForConfigYAML(false))],

            // with fallback
            [$this->getTransalatableChild($this->getTranslatorForConfigPHPInline())],
            [$this->getTransalatableChild($this->getTranslatorForConfigPHP())],
            [$this->getTransalatableChild($this->getTranslatorForConfigJSON())],
            [$this->getTransalatableChild($this->getTranslatorForConfigYAML())]
        ];
    }

    public function getTransalatableChild($translator)
    {
        $translatableChildMock = new ChildTranslatableMock();

        $appMock = new AppTranslatableMock();
        $appMock->translator = $translator;
        $appMock->add($translatableChildMock);

        return $translatableChildMock;
    }

    public function getTranslatorForRuntimeAdd()
    {
        $translator = new Translator();
        foreach(self::$translations_runtime_add as $string => $translations)
        {
            $translator->addOne($string, $translations);
        }
        return $translator;
    }

    public function getTranslatorForConfigPHPInline($use_fallback = false)
    {
        $translator = new Translator();
        $translator->setLanguage('en-inline',!$use_fallback ? null : 'en-inline');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test','php-inline');

        return $translator;
    }

    public function getTranslatorForConfigPHP($use_fallback = false)
    {
        $translator = new Translator();
        $translator->setLanguage('en',!$use_fallback ? null : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test','php');

        return $translator;
    }

    public function getTranslatorForConfigJSON($use_fallback = false)
    {

        $translator = new Translator();
        $translator->setLanguage('en',!$use_fallback ? null : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test','json');

        return $translator;
    }

    public function getTranslatorForConfigYAML($use_fallback = false)
    {
        $translator = new Translator();
        $translator->setLanguage('en',!$use_fallback ? null : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test','yaml');

        return $translator;
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testSingularFormNoCounter($translatableChild)
    {
        $trans = $translatableChild->_('string without counter');
        $this->assertEquals('string without counter translated', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testSingularForm($translatableChild)
    {
        $trans = $translatableChild->_('string not translated simple');
        $this->assertEquals('string translated', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testSingularFormNotExists($translatableChild)
    {
        $trans = $translatableChild->_('string not exists');
        $this->assertEquals('string not exists', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testPluralFormWithCounterZero($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 0);
        $this->assertEquals('string translated zero', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testPluralFormWithCounterOne($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 1);
        $this->assertEquals('string translated singular', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testPluralFormWithCounterTwo($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 2);
        $this->assertEquals('string translated plural', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testPluralsBiggerThanMaxPluralForm($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 300);
        $this->assertEquals('string translated plural', $trans);
    }

    /**
     * Test for string not found and return original string
     *
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testExceptionBadFormatEmptyString_noException($translatableChild)
    {
        $trans = $translatableChild->_('string with exception');
        $this->assertEquals('string with exception', $trans);
    }

    /**
     * this can happens only using ConfigTraitLoading
     *
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testExceptionBadFormatEmptyString($translatableChild)
    {
        // check if it was used the ConfigTrait
        if(empty($translatableChild->app->translator->config))
        {
            return;
        }

        $this->expectException(\atk4\core\Exception::class);
        $translatableChild->app->translator->raise_bad_format_exception = true;
        $translatableChild->_('string with exception string empty');
    }

    /**
     * @expectedException \atk4\core\Exception
     *
     * @dataProvider objectTranslatorProvider
     * @param $translatableChild
     */
    public function testExceptionBadFormatEmptyArray(ChildTranslatableMock $translatableChild)
    {
        $translatableChild->app->translator->raise_bad_format_exception = true;
        $translatableChild->_('string with exception array empty');
    }

    public function testFallbackLanguage()
    {
        $translator = new Translator();
        $translator->setLanguage('it-inline','en-inline');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test','php-inline');

        $mock = $this->getTransalatableChild($translator);

        // test for translation
        $trans = $mock->_('string fallback test');
        $this->assertEquals('fallback to en',$trans);

        // test fallback
    }
}

class AppTranslatableMock {

    use AppScopeTrait;
    use ContainerTrait;

    public $translator;

    public function __construct()
    {
        $this->app = $this;
    }

    public function _(string $message, int $count = 1, string $context = 'atk4'): string
    {
        return $this->translator->translate($message, $count, $context);
    }
}


class ChildTranslatableMock {
    use AppScopeTrait;
    use TranslatableTrait;
}