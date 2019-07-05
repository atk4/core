<?php

namespace atk4\core;

interface TranslatorInterface
{

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
    public function _($message, ?array $parameters = null, $domain = null, $locale = null) :string;
}
