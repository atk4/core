<?php

namespace atk4\core\Translator;

use atk4\core\Exception;
use atk4\core\Translator\Adapter\Generic;

/**
 * Translator is a bridge
 * -
 */
class Translator
{
    /** @var self */
    private static $instance;

    /** @var iTranslatorAdapter */
    private $adapter;

    protected $default_context = 'atk';
    protected $default_locale  = 'en';

    /**
     * Singleton no public constructor.
     */
    protected function __construct() {}

    /**
     * @param string $locale
     *
     * @return Translator
     */
    public function setDefaultLocale(string $locale): self
    {
        $this->default_locale = $locale;

        return $this;
    }

    /**
     * @param string $default_context
     *
     * @return Translator
     */
    public function setDefaultContext(string $default_context): self
    {
        $this->default_context = $default_context;

        return $this;
    }

    /**
     * No clone.
     */
    protected function __clone() {}

    /**
     * No serialize
     *
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception('Translator cannot be serialized');
    }

    /**
     * is a singleton and need a method to access the instance.
     */
    public static function instance() : self
    {
        if(null !== self::$instance) {
            return self::$instance;
        }

        return self::$instance = new static;
    }

    /**
     * Set the Translator Adapter. ( one time )
     *
     * @param iTranslatorAdapter $translator
     *
     * @return Translator
     */
    public function setAdapter(iTranslatorAdapter $translator) : self
    {
        $this->adapter = $translator;

        return $this;
    }

    /**
     * Adapter cannot be changed at runtime
     *
     * @return iTranslatorAdapter
     */
    protected function getAdapter() : iTranslatorAdapter
    {
        if(null !== $this->adapter)
        {
            return $this->adapter;
        }

        return $this->adapter = new Generic();
    }

    /**
     * Translate the given message.
     *
     * @param string      $message    The message to be translated
     * @param array       $parameters Array of parameters used to translate message
     * @param string|null $context     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     */
    public function _($message, array $parameters = [], ?string $context = null, ?string $locale = null): string
    {
        return $this->getAdapter()->_($message, $parameters, $context ?? $this->default_context, $locale ?? $this->default_locale);
    }
}