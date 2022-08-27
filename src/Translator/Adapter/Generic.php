<?php

declare(strict_types=1);

namespace Atk4\Core\Translator\Adapter;

use Atk4\Core\ConfigTrait;
use Atk4\Core\Translator\ITranslatorAdapter;

class Generic implements ITranslatorAdapter
{
    use ConfigTrait {
        getConfig as protected;
        readConfig as protected;
        setConfig as protected;
    }

    /** @var array<string, array<string, array<string, non-empty-array<string, string>>>> */
    protected array $definitions = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function _(string $message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        $definition = $this->getDefinition($message, $domain ?? 'atk', $locale ?? 'en');

        if ($definition === null) {
            return $message;
        }

        $count = $parameters['count'] ?? 1;

        return $this->processMessagePlural($definition, $parameters, $count);
    }

    /**
     * Return translated string. If parameters is not empty will replace tokens.
     *
     * @param array<string, mixed> $parameters
     */
    protected function processMessage(string $definition, array $parameters = []): string
    {
        foreach ($parameters as $key => $val) {
            $definition = str_replace('{{' . $key . '}}', (string) $val, $definition);
        }

        return $definition;
    }

    /**
     * @param non-empty-array<string, string> $definition
     * @param array<string, mixed>            $parameters
     * @param int                             $count      Requested plural form
     */
    protected function processMessagePlural(array $definition, array $parameters = [], int $count = 1): string
    {
        $foundDefinition = null;
        switch ($count) {
            case 0:
                $foundDefinition = $definition['zero'] ?? end($definition);

                break;
            case 1:
                $foundDefinition = $definition['one'] ?? null;

                break;
            default:
                $foundDefinition = $definition['other'] ?? null;

                break;
        }

        // if no definition found get the first from array
        if ($foundDefinition === null) {
            $foundDefinition = reset($definition);
        }

        return $this->processMessage($foundDefinition, $parameters);
    }

    /**
     * @return non-empty-array<string, string>|null
     */
    protected function getDefinition(string $message, string $domain, ?string $locale): ?array
    {
        return $this->definitions[$locale][$domain][$message] ?? null;
    }

    /**
     * @param array<string, string|non-empty-array<string, string>> $data
     */
    public function addDefinitionFromArray(array $data, string $locale, string $domain): void
    {
        foreach ($data as $k => $v) {
            $this->setDefinitionSingle($k, $v, $locale, $domain);
        }
    }

    /**
     * Set or Replace a single definition within a domain.
     *
     * @param string|non-empty-array<string, string> $definition
     *
     * @return $this
     */
    public function setDefinitionSingle(string $key, $definition, string $locale = 'en', string $domain = 'atk')
    {
        if (is_string($definition)) {
            $definition = ['one' => $definition];
        }

        $this->definitions[$locale][$domain][$key] = $definition;

        return $this;
    }
}
