<?php

declare(strict_types=1);

namespace Atk4\Core\Tests\Translator;

use Atk4\Core\Exception;
use Atk4\Core\TranslatableTrait;
use Atk4\Core\Translator\Adapter\Generic;
use Atk4\Core\Translator\Translator;
use Atk4\Data\Locale;

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
        $this->assertSame('no key return self', Translator::instance()->_('no key return self'));
    }

    public function testExceptionDiInstance(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'instance' => 'just to throw exception',
        ]);
    }

    public function testExceptionDiAdapter(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'adapter' => 'just to throw exception',
        ]);
    }

    public function testExceptionDiDefaultDomain(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'default_domain' => 123, // just to throw exception
        ]);
    }

    public function testExceptionDiDefaultLocale(): void
    {
        $this->expectException(Exception::class);
        Translator::instance()->setDefaults([
            'default_locale' => 123, // just to throw exception
        ]);
    }

    public function testAdapter(): void
    {
        $adapter = new Generic();

        // just to cover method addDefinitionFromFile
        $adapter->addDefinitionFromFile(Locale::getPath() . '/en/atk.php', 'en', 'atk', 'php');

        $adapter->setDefinitionSingle('test', 'custom definition', 'en', 'other');

        Translator::instance()->setAdapter($adapter);

        $this->assertSame('custom definition', Translator::instance()->_('test', [], 'other', 'en'));

        // test replace
        $adapter->setDefinitionSingle('test', 'custom definition replaced', 'en', 'other');

        $this->assertSame('custom definition replaced', Translator::instance()->_('test', [], 'other', 'en'));

        // test other language
        $adapter->setDefinitionSingle('test', 'definizione personalizzata', 'it', 'other');

        $this->assertSame('definizione personalizzata', Translator::instance()->_('test', [], 'other', 'it'));

        Translator::instance()->setDefaultLocale('it');
        $this->assertSame('definizione personalizzata', Translator::instance()->_('test', [], 'other'));

        Translator::instance()->setDefaultDomain('other');
        $this->assertSame('definizione personalizzata', Translator::instance()->_('test'));
    }

    public function testAdapterPlurals(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'zero' => 'is empty',
            'one' => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertSame('is empty', Translator::instance()->_('test', ['count' => 0]));
        $this->assertSame('is one', Translator::instance()->_('test', ['count' => 1]));
        $this->assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }

    public function testAdapterPluralsNotFullDefinition(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'one' => 'is one',
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertSame('is 0', Translator::instance()->_('test', ['count' => 0]));
        $this->assertSame('is one', Translator::instance()->_('test', ['count' => 1]));
        $this->assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }

    public function testAdapterPluralsSingular(): void
    {
        $adapter = new Generic();

        // test plurals
        Translator::instance()->setDefaults([
            'adapter' => $adapter,
            'default_domain' => 'other',
            'default_locale' => 'en',
        ]);

        $adapter->setDefinitionSingle('test', [
            'other' => 'is {{count}}',
        ], 'en', 'other');

        $this->assertSame('is 0', Translator::instance()->_('test', ['count' => 0]));
        $this->assertSame('is 1', Translator::instance()->_('test', ['count' => 1]));
        $this->assertSame('is 500', Translator::instance()->_('test', ['count' => 500]));
    }
}
