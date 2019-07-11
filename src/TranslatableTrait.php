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

    /**
     * Translates the given message.
     *
     * @param string      $message    The message to be translated
     * @param array       $parameters Array of parameters used to translate message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     */
    public function _($message, ?array $parameters = NULL, ?string $domain = NULL, ?string $locale = NULL): string
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($message, $parameters, $domain, $locale);
        }

        // if simple case string to string
        if (!$parameters) {
            return $message;
        }

        if(isset($parameters['%count%']))
        {
            return $this->processMessagePlural($message, $parameters);
        }

        return $this->processMessage($message, $parameters);
    }

    protected function processMessage(string $message, ?array $parameters = NULL): string
    {
        return str_replace(array_keys($parameters), array_values($parameters), $message);
    }

    protected function processMessagePlural(string $message, ?array $parameters = NULL): string
    {
        $message = explode('|',$message);

        if(count($message) === 1)
        {
            return $this->processMessage($message[0], $parameters);
        }

        $counter = (int) $parameters['%count%'] - 1;

        if($counter !== 0)
        {
            $counter = 1;
        }

        return $this->processMessage($message[$counter],$parameters);
    }
}
