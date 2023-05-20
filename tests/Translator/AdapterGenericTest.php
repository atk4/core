<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Translator;

use Atk4\Core\TranslatableTrait;
use Atk4\Core\Translator\Adapter\Generic;
use Atk4\Core\Translator\Translator;

class AdapterGenericTest extends AdapterBaseTest
{
    public function getTranslatableMock(): object
    {
        return new class() {
            use TranslatableTrait;
        };
    }

    public function testExceptionDiNotFound(): void
    {
        self::assertSame('no key return self', Translator::instance()->_('no key return self'));
    }

    public function testAdapter(): void
    {
        $adapter = new Generic();

        $adapter->setDefinitionSingle('test', 'custom definition', 'en', 'other');

        Translator::instance()->setAdapter($adapter);

        self::assertSame('custom definition', Translator::instance()->_('test', [], 'other', 'en'));

        // test replace
        $adapter->setDefinitionSingle('test', 'custom definition replaced', 'en', 'other');

        self::assertSame('custom definition replaced', Translator::instance()->_('test', [], 'other', 'en'));

        // test other language
        $adapter->setDefinitionSingle('test', 'definizione personalizzata', 'it', 'other');

        self::assertSame('definizione personalizzata', Translator::instance()->_('test', [], 'other', 'it'));

        Translator::instance()->setDefaultLocale('it');
        self::assertSame('definizione personalizzata', Translator::instance()->_('test', [], 'other'));

        Translator::instance()->setDefaultDomain('other');
        self::assertSame('definizione personalizzata', Translator::instance()->_('test'));
    }

    public function testAdapterPlurals(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'defaultDomain' => 'other',
            'defaultLocale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'zero' => 'is empty',
            'one' => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        self::assertSame('is empty', Translator::instance()->_('test', ['count' => 0]));
        self::assertSame('is one', Translator::instance()->_('test', ['count' => 1]));
        self::assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }

    public function testAdapterPluralsNotFullDefinition(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'defaultDomain' => 'other',
            'defaultLocale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'one' => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        self::assertSame('is 0', Translator::instance()->_('test', ['count' => 0]));
        self::assertSame('is one', Translator::instance()->_('test', ['count' => 1]));
        self::assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }

    public function testAdapterPluralsSingular(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'defaultDomain' => 'other',
            'defaultLocale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'other' => 'is {{count}}',
        ], 'en', 'other');

        self::assertSame('is 0', Translator::instance()->_('test', ['count' => 0]));
        self::assertSame('is 1', Translator::instance()->_('test', ['count' => 1]));
        self::assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }
}
