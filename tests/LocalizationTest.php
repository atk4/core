<?php

declare(strict_types=1);

namespace atk4\core\Tests;

use atk4\core\AtkPhpunit;
use atk4\core\Exception;
use atk4\core\Translator\Adapter\Generic;
use atk4\core\Translator\ITranslatorAdapter;
use atk4\core\Translator\Translator;
use atk4\data\Persistence;

class LocalizationTest extends AtkPhpunit\TestCase
{
    public function testTranslatableTrait()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (Exception $e) {
            $this->assertMatchesRegularExpression('/Невозможно определить постоянство драйвера из DSN/', $e->getHtml());
            $this->assertMatchesRegularExpression('/Невозможно определить постоянство драйвера из DSN/', $e->getColorfulText());
            $this->assertMatchesRegularExpression('/Невозможно определить постоянство драйвера из DSN/', $e->getJson());
        }
    }

    public function testTranslatableTrait2()
    {
        $adapter = new Generic();
        $adapter->setDefinitionSingle('Unable to determine persistence driver type from DSN', 'message is translated', 'en', 'atk');

        try {
            Persistence::connect('error:error');
        } catch (Exception $e) {
            $e->setTranslatorAdapter($adapter);
            $this->assertMatchesRegularExpression('/message is translated/', $e->getHtml());
            $this->assertMatchesRegularExpression('/message is translated/', $e->getColorfulText());
            $this->assertMatchesRegularExpression('/message is translated/', $e->getJson());
        }
    }

    public function testTranslatableExternal()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (Exception $e) {
            // emulate an external translator already configured
            $e->setTranslatorAdapter(new class() implements ITranslatorAdapter {
                public function _(
                    string $message,
                    array $parameters = [],
                    string $domain = null,
                    string $locale = null
                ): string {
                    return 'external translator';
                }
            });
            $this->assertMatchesRegularExpression('/external translator/', $e->getHtml());
            $this->assertMatchesRegularExpression('/external translator/', $e->getColorfulText());
            $this->assertMatchesRegularExpression('/external translator/', $e->getJson());
        }
    }
}
