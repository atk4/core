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
     * @throws Exception
     */
    public function setTranslator($translator)
    {
        if ($translator instanceof TranslatorInterface) {

            return $this->translator = $translator;
        }

        throw new Exception([
            'Translator must implements TranslatorInterface',
            'translator' => $translator
        ]);
    }

    /**
     * Translates the given message.
     *
     * @param string      $message    The message to be translated
     * @param array|null  $parameters Array of parameters used to translate message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     * @throws InvalidArgumentException If the locale contains invalid characters
     * @throws Exception
     *
     */
    public function _(
        string $message,
        ?array $parameters = NULL,
        ?string $domain = NULL,
        ?string $locale = NULL
    ): string {
        if ($this->translator === NULL) {
            throw new Exception('Translator for TranslatorSymfonyTrait must be defined with setTranslator');
        }

        return $this->translator->trans($message, $parameters, $domain, $locale);
    }
}