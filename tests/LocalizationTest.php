<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\Exception;
use atk4\core\TranslatableTrait;
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
            /** @var $e Exception */
            $e->translate();
            $this->assertEquals('Невозможно определить постоянство драйвера из DSN',$e->getMessage());
        }
    }

    public function testTranslatableExternal()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (\Throwable $e) {
            /** @var $e Exception */
            $e->translate(new class implements ITranslatorAdapter {

                public function _(string $message,
                    array $parameters = [],
                    ?string $domain = null,
                    ?string $locale = null): string
                {
                    return 'external translator';
                }
            });
            $this->assertEquals('external translator',$e->getMessage());
        }
    }
}