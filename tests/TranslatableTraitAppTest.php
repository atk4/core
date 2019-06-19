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
    /**
     * Get Child Object with TranslatableTrait
     *
     * @param Translator $translator
     *
     * @return ChildTranslatableMock
     * @throws Exception
     */
    private function getTranslatableChild(Translator $translator)
    {
        $translatableChild = new ChildTranslatableMock();

        $appMock             = new AppTranslatableMock();
        $appMock->translator = $translator;
        $appMock->add($translatableChild);

        return $translatableChild;
    }

    /**
     * Get Translator Class
     *
     * @param string      $format
     * @param string      $language
     * @param string|null $fallback
     *
     * @return Translator
     * @throws Exception
     */
    private function getTranslator(string $format, string $language, ?string $fallback = NULL): Translator
    {
        $translator = new Translator($language, $fallback);
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', $format);

        return $translator;
    }

    /**
     * Test method Translator->addOne ( runtime addition of translations )
     */
    public function testAddOne()
    {
        $translations = [
            'string without counter'                               => ['string without counter translated'],
            'string not translated simple'                         => [
                1 => 'string translated',
            ],
            'string not translated with plurals'                   => [
                0 => 'string translated zero',
                1 => 'string translated singular',
                2 => 'string translated plural',
            ],
            'string with exception array empty'                    => [],
            'string fallback test'                                 => ['fallback to en'],
            'no-counter: %s, zero: %s, singular : %s, plural : %s' => ['translated - no-counter: %s, zero: %s, singular : %s, plural : %s']
        ];

        $translator = new Translator('it', 'en');

        foreach ($translations as $string => $translation) {
            $translator->addOne($string, $translation);
        }

        $mock = $this->getTranslatableChild($translator);

        // against translation without plural forms
        $trans = $mock->_('string without counter');
        $this->assertEquals('string without counter translated', $trans);

        // against translation without plural forms asking for plural that not exists
        $trans = $mock->_('string without counter', 2);
        $this->assertEquals('string without counter translated', $trans);

        // against translation with plural form without counter
        $trans = $mock->_('string not translated with plurals');
        $this->assertEquals('string translated singular', $trans);

        // against translation with plural form with counter
        $trans = $mock->_('string not translated with plurals', 1);
        $this->assertEquals('string translated singular', $trans);

        // against translation with plural form with counter 0 = zero form
        $trans = $mock->_('string not translated with plurals', 0);
        $this->assertEquals('string translated zero', $trans);

        // against translation with plural form with counter 2 = first plural form
        $trans = $mock->_('string not translated with plurals', 2);
        $this->assertEquals('string translated plural', $trans);

        // against translation with plural form with counter greater than available plural forms
        $trans = $mock->_('string not translated with plurals', 200);
        $this->assertEquals('string translated plural', $trans);
    }

    /**
     * Test method Translator->addOne for :
     * raising Exception when adding an already defined translation
     *
     * @expectedException \atk4\core\Exception
     */
    public function testExceptionAddOne_ifStringExists()
    {
        $translations = [
            'string without counter'                               => ['string without counter translated'],
            'string not translated simple'                         => [
                1 => 'string translated',
            ],
            'string not translated with plurals'                   => [
                0 => 'string translated zero',
                1 => 'string translated singular',
                2 => 'string translated plural',
            ],
            'string with exception array empty'                    => [],
            'string fallback test'                                 => ['fallback to en'],
            'no-counter: %s, zero: %s, singular : %s, plural : %s' => ['translated - no-counter: %s, zero: %s, singular : %s, plural : %s']
        ];

        $translator = new Translator('it', 'en');

        foreach ($translations as $string => $translation) {
            $translator->addOne($string, $translation);
        }

        //this will throw an exception
        $translator->addOne('string without counter', ['string without counter translated']);
    }

    /**
     * Test method Translator->addOne for :
     * NOT raising Exception when adding an already defined translation
     * using argument $replace = true
     */
    public function testExceptionAddOne_NotRaise()
    {
        $translations = [
            'string without counter'                               => ['string without counter translated'],
            'string not translated simple'                         => [
                1 => 'string translated',
            ],
            'string not translated with plurals'                   => [
                0 => 'string translated zero',
                1 => 'string translated singular',
                2 => 'string translated plural',
            ],
            'string with exception array empty'                    => [],
            'string fallback test'                                 => ['fallback to en'],
            'no-counter: %s, zero: %s, singular : %s, plural : %s' => ['translated - no-counter: %s, zero: %s, singular : %s, plural : %s']
        ];

        $translator = new Translator('it', 'en');

        foreach ($translations as $string => $translation) {
            $translator->addOne($string, $translation);
        }

        //this will throw an exception
        $translator->addOne('string without counter', ['string without counter translated'], 'atk4', true);
    }

    /**
     * Test Loading of translations
     */
    public function testLoadFromFolder()
    {
        $excepted = require __DIR__ . DIRECTORY_SEPARATOR . 'translator_test' . DIRECTORY_SEPARATOR . 'en-inline.php';

        $translator = $this->getTranslator('php', 'en');
        $mock       = $this->getTranslatableChild($translator);

        foreach ($excepted as $domain => $domains) {
            foreach ($domains as $string => $trans) {
                if (!empty($trans) && is_array($trans)) {
                    $trans = $trans[1] ?? $trans[0];

                    $result = $mock->_d($domain, $string);
                    $this->assertEquals($trans, $result);
                }
            }
        }
    }

    /**
     * Test Loading of translations with fallback
     */
    public function testLoadFromFolderWithFallback()
    {
        $primary  = require __DIR__ . DIRECTORY_SEPARATOR . 'translator_test' . DIRECTORY_SEPARATOR . 'en-inline.php';
        $fallback = require __DIR__ . DIRECTORY_SEPARATOR . 'translator_test' . DIRECTORY_SEPARATOR . 'it-inline.php';

        $excepted = array_replace_recursive($fallback, $primary);

        $translator = $this->getTranslator('php', 'en', 'it');
        $mock       = $this->getTranslatableChild($translator);

        foreach ($excepted as $domain => $domains) {
            foreach ($domains as $string => $trans) {
                if (!empty($trans) && is_array($trans)) {
                    $trans = $trans[1] ?? $trans[0];

                    $result = $mock->_d($domain, $string);
                    $this->assertEquals($trans, $result);
                }
            }
        }
    }

    public function testFallbackLanguage()
    {
        $translator = new Translator('it-inline', 'en-inline');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php-inline');

        $mock = $this->getTranslatableChild($translator);

        // test for italian translation
        $trans = $mock->_('string not translated with plurals', 1);
        $this->assertEquals('frase forma plurale uno', $trans);

        // test fallback
        $trans = $mock->_('string fallback test');
        $this->assertEquals('fallback to en', $trans);
    }

    /**
     * Test Loading of translations - Json format
     */
    public function testLoadFromFolder_JSON()
    {
        $excepted = require __DIR__ . DIRECTORY_SEPARATOR . 'translator_test' . DIRECTORY_SEPARATOR . 'en-inline.php';

        $translator = $this->getTranslator('json', 'en');
        $mock       = $this->getTranslatableChild($translator);

        foreach ($excepted as $domain => $domains) {
            foreach ($domains as $string => $trans) {
                if (!empty($trans) && is_array($trans)) {
                    $trans = $trans[1] ?? $trans[0];

                    $result = $mock->_d($domain, $string);
                    $this->assertEquals($trans, $result);
                }
            }
        }
    }

    /**
     * Test Loading of translations - Yaml format
     */
    public function testLoadFromFolder_YAML()
    {

        $excepted = require __DIR__ . DIRECTORY_SEPARATOR . 'translator_test' . DIRECTORY_SEPARATOR . 'en-inline.php';

        $translator = $this->getTranslator('yaml', 'en');
        $mock       = $this->getTranslatableChild($translator);

        foreach ($excepted as $domain => $domains) {
            foreach ($domains as $string => $trans) {
                if (!empty($trans) && is_array($trans)) {
                    $trans = $trans[1] ?? $trans[0];

                    $result = $mock->_d($domain, $string);
                    $this->assertEquals($trans, $result);
                }
            }
        }
    }

    /**
     * @expectedException \atk4\core\Exception
     */
    public function testExceptionBadFormatEmptyString()
    {
        $translator = $this->getTranslator('php', 'en');
        $mock       = $this->getTranslatableChild($translator);

        $mock->app->translator->raise_bad_format_exception = true;

        // this call raise exception
        $mock->_('string with exception string empty');
    }

    public function testExceptionBadFormatEmptyString_NotRaise()
    {
        $translator = $this->getTranslator('php', 'en');
        $mock       = $this->getTranslatableChild($translator);

        $trans = $mock->_('string with exception string empty');
        $this->assertEquals('string with exception string empty', $trans);
    }

    /**
     * @expectedException \atk4\core\Exception
     */
    public function testExceptionBadFormatEmptyArray()
    {
        $translator = $this->getTranslator('php', 'en');
        $mock       = $this->getTranslatableChild($translator);

        $mock->app->translator->raise_bad_format_exception = true;

        // this call raise exception
        $mock->_('string with exception array empty');
    }

    public function testExceptionBadFormatEmptyArray_NotRaise()
    {
        $translator = $this->getTranslator('php', 'en');
        $mock       = $this->getTranslatableChild($translator);

        $trans = $mock->_('string with exception array empty');
        $this->assertEquals('string with exception array empty', $trans);
    }

    public function testDifferentDomain()
    {
        $translator = new Translator('it', 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php');

        $mock = $this->getTranslatableChild($translator);

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
        $translator = new Translator('it', 'en');
        $translator->addFromFolder(__DIR__ . DIRECTORY_SEPARATOR . 'translator_test', 'php');

        $mock = $this->getTranslatableChild($translator);

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
            $mock->_('string without counter'), $mock->_d('other-domain', 'string not translated with plurals', 0),
            $mock->_d('atk4', 'string not translated with plurals', 1),
            $mock->_d('other-domain', 'string not translated with plurals', 2));

        // test other multi with context domains
        $trans = $mock->_md('other-domain', 'no-counter: %s, zero: %s, singular : %s, plural : %s', [
            ['string without counter'],
            ['other-domain', 'string not translated with plurals', 0],
            ['atk4', 'string not translated with plurals', 1],
            ['other-domain', 'string not translated with plurals', 2]
        ]);

        $this->assertEquals($excepted, $trans);
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