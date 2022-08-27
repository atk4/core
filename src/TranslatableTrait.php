<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Core\Translator\Translator;

/**
 * If a class use this trait, string can be translated calling method translate.
 */
trait TranslatableTrait
{
    /**
     * Translates the given message.
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
        if (TraitUtil::hasAppScopeTrait($this) && $this->issetApp() && method_exists($this->getApp(), '_')) {
            return $this->getApp()->_($message, $parameters, $domain, $locale);
        }

        return Translator::instance()->_($message, $parameters, $domain, $locale);
    }
}
