<?php

declare(strict_types=1);

namespace Atk4\Core\Translator;

interface ITranslatorAdapter
{
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
    public function _(string $message, array $parameters = [], string $domain = null, string $locale = null): string;
}
