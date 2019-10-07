<?php

namespace atk4\core\tests;

use atk4\core\Exception;
use atk4\core\Translator\Adapter\Generic;
use atk4\core\Translator\ITranslatorAdapter;
use atk4\core\Translator\Translator;
use atk4\data\Persistence;
use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    public function testTranslatableTrait()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (\Throwable $e) {
            /* @var $e Exception */
            $this->assertRegExp('/Невозможно определить постоянство драйвера из DSN/', $e->getHTML());
            $this->assertRegExp('/Невозможно определить постоянство драйвера из DSN/', $e->getHTMLText());
            $this->assertRegExp('/Невозможно определить постоянство драйвера из DSN/', $e->getColorfulText());
            $this->assertRegExp('/Невозможно определить постоянство драйвера из DSN/', $e->getJSON());
        }
    }

    public function testTranslatableTrait2()
    {
        $adapter = new Generic();
        $adapter->setDefinitionSingle('Unable to determine persistence driver from DSN','message is translated','en','atk');

        try {
            Persistence::connect('error:error');
        } catch (\Throwable $e) {
            /* @var $e Exception */
            $e->setTranslatorAdapter($adapter);
            $this->assertRegExp('/message is translated/', $e->getHTML());
            $this->assertRegExp('/message is translated/', $e->getHTMLText());
            $this->assertRegExp('/message is translated/', $e->getColorfulText());
            $this->assertRegExp('/message is translated/', $e->getJSON());
        }
    }

    public function testTranslatableExternal()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (\Throwable $e) {
            /* @var $e Exception */
            // emulate an external translator already configured
            $e->setTranslatorAdapter(new class() implements ITranslatorAdapter {
                public function _(string $message,
                    array $parameters = [],
                    ?string $domain = null,
                    ?string $locale = null): string
                {
                    return 'external translator';
                }
            });
            $this->assertRegExp('/external translator/', $e->getHTML());
            $this->assertRegExp('/external translator/', $e->getHTMLText());
            $this->assertRegExp('/external translator/', $e->getColorfulText());
            $this->assertRegExp('/external translator/', $e->getJSON());
        }
    }
}
