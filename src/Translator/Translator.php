<?php

declare(strict_types=1);

namespace Atk4\Core\Translator;

use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\Translator\Adapter\Generic;

/**
 * Translator is a bridge.
 */
class Translator
{
    use DiContainerTrait {
        setDefaults as private _setDefaults;
    }

    /** @var self */
    private static $instance;

    /** @var ITranslatorAdapter */
    private $adapter;

    /** @var string Default domain of translations */
    protected $defaultDomain = 'atk';

    /** @var string Default language of translations */
    protected $defaultLocale = 'en';

    /**
     * Singleton no public constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Set property like dependency injection.
     */
    public function setDefaults(array $properties, bool $passively = false): void
    {
        if (null !== ($properties['instance'] ?? null)) {
            throw new Exception('$instance cannot be replaced');
        }

        $adapter = $properties['adapter'] ?? null;
        if ($adapter !== null && !($adapter instanceof ITranslatorAdapter)) {
            throw new Exception('$adapter must be an instance of ITranslatorAdapter');
        }

        if (!is_string($properties['defaultDomain'] ?? '')) {
            throw new Exception('defaultDomain must be string');
        }

        if (!is_string($properties['defaultLocale'] ?? '')) {
            throw new Exception('defaultLocale must be string');
        }

        $this->_setDefaults($properties);
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

    /**
     * No clone.
     *
     * @codeCoverageIgnore
     */
    protected function __clone()
    {
        throw new Exception('Translator cannot be cloned');
    }

    /**
     * No serialize.
     *
     * @codeCoverageIgnore
     */
    public function __wakeup(): void
    {
        throw new Exception('Translator cannot be serialized');
    }

    /**
     * Is a singleton and need a method to access the instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Set the Translator Adapter.
     */
    public function setAdapter(ITranslatorAdapter $translator): self
    {
        $this->adapter = $translator;

        return $this;
    }

    /**
     * Get the adapter.
     *
     * @TODO should remain private?
     */
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
     * @param string      $message    The message to be translated
     * @param array       $parameters Array of parameters used to translate message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     */
    public function _(string $message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->getAdapter()->_($message, $parameters, $domain ?? $this->defaultDomain, $locale ?? $this->defaultLocale);
    }
}
