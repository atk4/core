<?php

namespace atk4\core\Translator\Adapter;

use atk4\core\ConfigTrait;
use atk4\core\Translator\iTranslatorAdapter;
use atk4\data\Locale;

class Generic implements iTranslatorAdapter
{
    use ConfigTrait {
        getConfig as protected;
        readConfig as protected;
        setConfig as protected;
    }

    protected $definitions = [];

    /**
     * @inheritDoc
     */
    public function _(string $message, array $parameters = [], ?string $context = null, ?string $locale = null): string
    {
        $definition = $this->getDefinition($message, $context, $locale);

        if(null === $definition)
        {
            return $message;
        }

        $count = $parameters['count'] ?? null;

        if (null !== $count) {
            return $this->processMessagePlural($definition, $parameters, $count);
        }

        return $this->processMessage($definition, $parameters);
    }

    /**
     * Return translated string.
     * if parameters is not empty will replace tokens.
     *
     * @param array|string     $definition
     * @param array|null $parameters
     *
     * @return string
     */
    protected function processMessage($definition, array $parameters = []): string
    {
        foreach($parameters as $key => $val)
        {
            $definition = str_replace('{{'.$key.'}}', $val, $definition);
        }

        return $definition;
    }

    /**
     * @param array|string $definition A string of definitions separated by |
     * @param array  $parameters An array of parameters
     * @param int    $count      Requested plural form
     *
     * @return string
     */
    protected function processMessagePlural($definition, array $parameters = [], int $count = 1): string
    {
        $definitions_forms = is_array($definition) ? $definition : explode('|', $definition);

        switch($count)
        {
            case 0:
                $definition = $definitions_forms['zero'] ?? $definitions_forms[0] ?? null;
                break;
            case 1:
                $definition = $definitions_forms['one'] ?? $definitions_forms[1] ?? null;
                break;
            default:
                $definition = $definitions_forms['other'] ?? end($definitions_forms);
                break;
        }

        return $this->processMessage($definition, $parameters);
    }

    protected function getDefinition(string $message, $context, ?string $locale)
    {
        if (!isset($this->definitions[$locale])) {
            $this->loadDefinitions($locale);
        }

        return $this->definitions[$locale][$context][$message] ?? null;
    }

    protected function loadDefinitions(string $locale)
    {
        if (class_exists('\atk4\data\Locale')) {

            $path = Locale::getPath();

            $this->readConfig($path.$locale.'/atk.php','php-inline');

            $this->definitions = array_replace_recursive(
                $this->definitions,
                [
                    $locale => [
                        'atk' => $this->config
                    ]
                ]);

            $this->config = [];
        }
    }
}