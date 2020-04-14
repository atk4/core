<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\Exception;
use atk4\core\TranslatableTrait;
use atk4\core\Translator\Adapter\Generic;
use atk4\core\Translator\Translator;
use atk4\data\Locale;

class TranslatorAdapterGenericTest extends TranslatorAdapterBase
{
    public function getTranslatableMock()
    {
        return new class() {
            use TranslatableTrait;
        };
    }

    public function testExceptionDI_not_found(): void
    {
        $this->assertEquals('no key return self', Translator::instance()->_('no key return self'));
    }

    public function testExceptionDI_instance(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'instance' => 'just to throw exception',
        ]);
    }

    public function testExceptionDI_adapter(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'adapter' => 'just to throw exception',
        ]);
    }

    public function testExceptionDI_default_domain(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'default_domain' => 123, /* just to throw exception*/
        ]);
    }

    public function testExceptionDI_default_locale(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'default_locale' => 123, /* just to throw exception*/
        ]);
    }

    public function testAdapter(): void
    {
        $adapter = new Generic();

        /* just to cover method addDefinitionFromFile*/
        $adapter->addDefinitionFromFile(Locale::getPath() . '/en/atk.php', 'en', 'atk', 'php-inline');

        $adapter->setDefinitionSingle('test', 'custom definition', 'en', 'other');

        Translator::instance()->setAdapter($adapter);

        $this->assertEquals('custom definition', Translator::instance()->_('test', [], 'other', 'en'));

        // test replace
        $adapter->setDefinitionSingle('test', 'custom definition replaced', 'en', 'other');

        $this->assertEquals('custom definition replaced', Translator::instance()->_('test', [], 'other', 'en'));

        // test other language
        $adapter->setDefinitionSingle('test', 'definizione personalizzata', 'it', 'other');

        $this->assertEquals('definizione personalizzata', Translator::instance()->_('test', [], 'other', 'it'));

        Translator::instance()->setDefaultLocale('it');
        $this->assertEquals('definizione personalizzata', Translator::instance()->_('test', [], 'other'));

        Translator::instance()->setDefaultDomain('other');
        $this->assertEquals('definizione personalizzata', Translator::instance()->_('test'));
    }

    public function testAdapterPlurals(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter'        => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'zero'  => 'is empty',
            'one'   => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertEquals('is empty', Translator::instance()->_('test', ['count' =>0]));
        $this->assertEquals('is one', Translator::instance()->_('test', ['count' =>1]));
        $this->assertEquals('is 500', Translator::instance()->_('test', ['count' =>500]));
    }

    public function testAdapterPlurals_notFullDefinition(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter'        => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'one'   => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertEquals('is 0', Translator::instance()->_('test', ['count' =>0]));
        $this->assertEquals('is one', Translator::instance()->_('test', ['count' =>1]));
        $this->assertEquals('is 500', Translator::instance()->_('test', ['count' =>500]));
    }

    public function testAdapterPlurals_Singular(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter'        => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertEquals('is 0', Translator::instance()->_('test', ['count' =>0]));
        $this->assertEquals('is 1', Translator::instance()->_('test', ['count' =>1]));
        $this->assertEquals('is 500', Translator::instance()->_('test', ['count' =>500]));
    }
}
