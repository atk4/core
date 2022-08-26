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

    /** @var array */
    protected $definitions = [];

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
     * Return translated string.
     * if parameters is not empty will replace tokens.
     */
    protected function processMessage(string $definition, array $parameters = []): string
    {
        foreach ($parameters as $key => $val) {
            $definition = str_replace('{{' . $key . '}}', (string) $val, $definition);
        }

        return $definition;
    }

    /**
     * @param array|string $definition A string of definitions separated by |
     * @param array        $parameters An array of parameters
     * @param int          $count      Requested plural form
     */
    protected function processMessagePlural($definition, array $parameters = [], int $count = 1): string
    {
        $definitionsForms = is_array($definition) ? $definition : explode('|', $definition);
        $foundDefinition = null;
        switch ($count) {
            case 0:
                $foundDefinition = $definitionsForms['zero'] ?? end($definitionsForms);

                break;
            case 1:
                $foundDefinition = $definitionsForms['one'] ?? null;

                break;
            default:
                $foundDefinition = $definitionsForms['other'] ?? null;

                break;
        }

        // if no definition found get the first from array
        $definition = $foundDefinition ?? array_shift($definitionsForms);

        return $this->processMessage($definition, $parameters);
    }

    /**
     * @return array|string|null
     */
    protected function getDefinition(string $message, string $domain, ?string $locale)
    {
        return $this->definitions[$locale][$domain][$message] ?? null;
    }

    public function addDefinitionFromArray(array $data, string $locale, string $domain): void
    {
        $this->definitions = array_replace_recursive(
            $this->definitions,
            [
                $locale => [
                    $domain => $data,
                ],
            ]
        );
    }

    /**
     * Set or Replace a single definition within a domain.
     *
     * @param string|array $definition
     *
     * @return $this
     */
    public function setDefinitionSingle(string $key, $definition, string $locale = 'en', string $domain = 'atk')
    {
        if (is_string($definition)) {
            $definition = [$definition];
        }

        $this->definitions[$locale][$domain][$key] = $definition;

        return $this;
    }
}
