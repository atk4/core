<?php

namespace atk4\core;

/**
 * If a class use this trait, string can be translated calling method translate.
 */
trait TranslatableTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_translatableTrait = true;

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * get Translator object
     *
     * @return TranslatorInterface
     */
    public function getTranslator() : ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Translates the given message.
     *
     * @param string       $message    The message to be translated
     * @param array        $parameters Array of parameters used to translate message
     * @param string|null  $domain     The domain for the message or null to use the default
     * @param string|null  $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     */
    public function _($message, ?array $parameters = null, $domain = null, $locale = null) :string
    {
        if(isset($this->app) && method_exists($this->app, '_'))
        {
            return $this->app->_($message, $parameters, $domain, $locale);
        }

        if(empty($parameters))
        {
            return $message;
        }
        
        /**
         * @see https://symfony.com/doc/current/translation/message_format.html
         */

        array_map_assoc(function($k,$v) {
            return ["{" . $k . "}",$v];
        },$parameters);
        
        return str_replace(array_keys($parameters), array_values($parameters), $message);
    }
}
