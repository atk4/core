<?php

declare(strict_types=1);

namespace Atk4\Core\Translator;

use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Translator\Adapter\Generic;

/**
 * @phpstan-consistent-constructor
 */
class Translator
{
    use DiContainerTrait;

    private static ?self $instance = null;

    private ?ITranslatorAdapter $adapter = null;

    protected string $defaultDomain = 'atk';

    protected string $defaultLocale = 'en';

    private function __construct()
    {
        // singleton
    }

    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    public function setDefaultDomain(string $defaultDomain): self
    {
        $this->defaultDomain = $defaultDomain;

        return $this;
    }

    private function __clone()
    {
        // prevent cloning
    }

    public function __sleep(): array
    {
        throw new Exception('Serialization is not supported');
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function setAdapter(ITranslatorAdapter $translator): self
    {
        $this->adapter = $translator;

        return $this;
    }

    private function getAdapter(): ITranslatorAdapter
    {
        if ($this->adapter === null) {
            $this->adapter = new Generic();
        }

        return $this->adapter;
    }

    /**
     * Translate the given message.
     *
     * @param string               $message    The message to be translated
     * @param array<string, mixed> $parameters Array of parameters used to translate message
     * @param string|null          $domain     The domain for the message or null to use the default
     * @param string|null          $locale     The locale or null to use the default
     *
     * @return string The translated string
     */
    public function _(string $message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->getAdapter()->_($message, $parameters, $domain ?? $this->defaultDomain, $locale ?? $this->defaultLocale);
    }
}
