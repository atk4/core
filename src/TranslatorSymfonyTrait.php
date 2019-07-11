<?php

namespace atk4\core;

use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorSymfonyTrait
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     *
     * @return TranslatorInterface
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        return $this->translator = $translator;
    }

    /**
     * Translates the given message.
     *
     * @param string      $message    The message to be translated
     * @param array|null  $parameters Array of parameters used to translate message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     * @throws Exception
     *
     * @return string The translated string
     */
    public function _(
        string $message,
        ?array $parameters = null,
        ?string $domain = null,
        ?string $locale = null
    ): string {
        if ($this->translator === null) {
            throw new Exception('Translator for TranslatorSymfonyTrait must be defined with setTranslator');
        }

        return $this->translator->trans($message, $parameters, $domain, $locale);
    }
}
