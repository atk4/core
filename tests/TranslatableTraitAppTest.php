<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\Exception;
use atk4\core\TranslatableTrait;
use atk4\core\Translator;
use PHPUnit_Framework_TestCase;

class TranslatableTraitAppTest extends PHPUnit_Framework_TestCase
{
    public static $translations_runtime_add = [
        'string without counter'             => ['string without counter translated'],
        'string not translated simple'       => [
            1 => 'string translated',
        ],
        'string not translated with plurals' => [
            0 => 'string translated zero',
            1 => 'string translated singular',
            2 => 'string translated plural',
        ],
        'string with exception array empty'  => []
    ];
    public $appMock;
    public $translatableChildMock;
    public $skip_case_string_empty = true;

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

        $appMock             = new AppTranslatableMock();
        $appMock->translator = $translator;
        $appMock->add($translatableChildMock);

        return $translatableChildMock;
    }

    public function getTranslatorForRuntimeAdd()
    {
        $translator = new Translator('en','en');
        foreach (self::$translations_runtime_add as $string => $translations) {
            $translator->addOne($string, $translations);
        }
        return $translator;
    }

    public function getTranslatorForConfigPHPInline($use_fallback = false)
    {
        $translator = new Translator('en-inline', !$use_fallback ? NULL : 'en-inline');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php-inline');

        return $translator;
    }

    public function getTranslatorForConfigPHP($use_fallback = false)
    {
        $translator = new Translator('en', !$use_fallback ? NULL : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php');

        return $translator;
    }

    public function getTranslatorForConfigJSON($use_fallback = false)
    {

        $translator = new Translator('en', !$use_fallback ? NULL : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'json');

        return $translator;
    }

    public function getTranslatorForConfigYAML($use_fallback = false)
    {
        $translator = new Translator('en', !$use_fallback ? NULL : 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'yaml');

        return $translator;
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testSingularFormNoCounter($translatableChild)
    {
        $trans = $translatableChild->_('string without counter');
        $this->assertEquals('string without counter translated', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testSingularForm($translatableChild)
    {
        $trans = $translatableChild->_('string not translated simple');
        $this->assertEquals('string translated', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testSingularFormNotExists($translatableChild)
    {
        $trans = $translatableChild->_('string not exists');
        $this->assertEquals('string not exists', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testPluralFormWithCounterZero($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 0);
        $this->assertEquals('string translated zero', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testPluralFormWithCounterOne($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 1);
        $this->assertEquals('string translated singular', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testPluralFormWithCounterTwo($translatableChild)
    {
        $trans = $translatableChild->_('string not translated with plurals', 2);
        $this->assertEquals('string translated plural', $trans);
    }

    /**
     * @dataProvider objectTranslatorProvider
     *
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
     *
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
     *
     * @param $translatableChild
     */
    public function testExceptionBadFormatEmptyString($translatableChild)
    {
        // check if it was used the ConfigTrait
        if (empty($translatableChild->app->translator->config)) {
            return;
        }

        $this->expectException(Exception::class);
        $translatableChild->app->translator->raise_bad_format_exception = true;
        $translatableChild->_('string with exception string empty');
    }

    /**
     * @expectedException Exception
     *
     * @dataProvider objectTranslatorProvider
     *
     * @param $translatableChild
     */
    public function testExceptionBadFormatEmptyArray(ChildTranslatableMock $translatableChild)
    {
        $translatableChild->app->translator->raise_bad_format_exception = true;
        $translatableChild->_('string with exception array empty');
    }

    public function testFallbackLanguage()
    {
        $mock = $this->getTranslatableChildWithFallback();

        // test for italian translation
        $trans = $mock->_('string not translated with plurals', 1);
        $this->assertEquals('frase forma plurale uno', $trans);

        // test fallback
        $trans = $mock->_('string fallback test');
        $this->assertEquals('fallback to en', $trans);
    }

    public function getTranslatableChildWithFallback()
    {
        $translator = new Translator('it-inline', 'en-inline');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php-inline');

        return $this->getTransalatableChild($translator);
    }

    public function testDifferentDomain()
    {
        $mock = $this->getTranslatableChildWithFallback();

        // test without domain italian translation
        $trans = $mock->_('string without counter');
        $this->assertEquals('frase senza contatore', $trans);

        // test with domain for italian translation
        $trans = $mock->_d('atk4', 'string without counter');
        $this->assertEquals('frase senza contatore', $trans);

        // test other domain without counter for italian translation
        $trans = $mock->_d('other-domain', 'string without counter');
        $this->assertEquals('altro dominio stessa stringa', $trans);

        // test other domain with counter for italian translation
        $trans = $mock->_d('other-domain', 'string without counter', 1);
        $this->assertEquals('altro dominio stessa stringa', $trans);
    }

    public function testMultiTranslation()
    {
        $mock = $this->getTranslatableChildWithFallback();

        // test other multi without context domain
        $trans = $mock->_m('no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['string not translated with plurals', 0],
            ['string not translated with plurals', 1],
            ['string not translated with plurals', 2]
        ]);

        $this->assertEquals('no-plurale: frase senza contatore, forma zero: frase forma plurale zero, forma singolare : frase forma plurale uno, forma plurale : frase forma plurale due',
            $trans);

        // test other multi with context domains
        $trans = $mock->_md('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['string not translated with plurals', 0],
            ['string not translated with plurals', 1],
            ['string not translated with plurals', 2]
        ]);

        $this->assertEquals('altro dominio | no-plurale: frase senza contatore, forma zero: frase forma plurale zero, forma singolare : frase forma plurale uno, forma plurale : frase forma plurale due',
            $trans);

        // test other multi with context domains
        $trans = $mock->_md('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['string not translated with plurals', 0],
            ['string not translated with plurals', 1],
            ['string not translated with plurals', 2]
        ]);

        $this->assertEquals('altro dominio | no-plurale: frase senza contatore, forma zero: frase forma plurale zero, forma singolare : frase forma plurale uno, forma plurale : frase forma plurale due',
            $trans);

        // test other multi with context domains
        $trans = $mock->_md('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['other-domain', 'string not translated with plurals', 0],
            ['atk4', 'string not translated with plurals', 1],
            ['other-domain', 'string not translated with plurals', 2]
        ]);
        $this->assertEquals('altro dominio | no-plurale: frase senza contatore, forma zero: altro dominio frase forma plurale zero, forma singolare : frase forma plurale uno, forma plurale : altro dominio frase forma plurale due',
            $trans);

        $excepted = sprintf($mock->_d('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s'),
            $mock->_('string without counter'),
            $mock->_d('other-domain', 'string not translated with plurals', 0),
            $mock->_d('atk4', 'string not translated with plurals', 1),
            $mock->_d('other-domain', 'string not translated with plurals', 2)
        );

        // test other multi with context domains
        $trans = $mock->_md('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['other-domain', 'string not translated with plurals', 0],
            ['atk4', 'string not translated with plurals', 1],
            ['other-domain', 'string not translated with plurals', 2]
        ]);

        $this->assertEquals($excepted,$trans);
    }
}

class AppTranslatableMock
{

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


class ChildTranslatableMock
{
    use AppScopeTrait;
    use TranslatableTrait;
}