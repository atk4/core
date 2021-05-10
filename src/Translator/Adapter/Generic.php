<?php

declare(strict_types=1);

namespace Atk4\Core\Translator\Adapter;

use Atk4\Core\ConfigTrait;
use Atk4\Core\Translator\ITranslatorAdapter;
use Atk4\Data\Locale;

class Generic implements ITranslatorAdapter
{
    use ConfigTrait {
        getConfig as protected;
        readConfig as protected;
        setConfig as protected;
    }

    /** @var array */
    protected $definitions = [];

    /**
     * {@inheritdoc}
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
        $definitions_forms = is_array($definition) ? $definition : explode('|', $definition);
        $found_definition = null;
        switch ((int) $count) {
            case 0:
                $found_definition = $definitions_forms['zero'] ?? end($definitions_forms);

                break;
            case 1:
                $found_definition = $definitions_forms['one'] ?? null;

                break;
            default:
                $found_definition = $definitions_forms['other'] ?? null;

                break;
        }

        // if no definition found get the first from array
        $definition = $found_definition ?? array_shift($definitions_forms);

        return $this->processMessage($definition, $parameters);
    }

    /**
     * @return array|string|null
     */
    protected function getDefinition(string $message, $domain, ?string $locale)
    {
        $this->loadDefinitionAtk($locale); // need to be called before manual add

        return $this->definitions[$locale][$domain][$message] ?? null;
    }

    protected function loadDefinitionAtk(string $locale): void
    {
        if (isset($this->definitions[$locale]['atk'])) {
            return;
        }

        $this->definitions[$locale]['atk'] = [];

        if (class_exists(\Atk4\Data\Locale::class)) {
            $path = Locale::getPath();
            $this->addDefinitionFromFile($path . '/' . $locale . '/atk.php', $locale, 'atk', 'php');
        }
    }

    /**
     * Load definitions from file.
     */
    public function addDefinitionFromFile(string $file, string $locale, string $domain, string $format): void
    {
        $this->loadDefinitionAtk($locale); // need to be called before manual add

        $this->readConfig($file, $format);

        $this->definitions = array_replace_recursive(
            $this->definitions,
            [
                $locale => [
                    $domain => $this->config,
                ],
            ]
        );

        $this->config = [];
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
        $this->loadDefinitionAtk($locale); // need to be called before manual add

        if (is_string($definition)) {
            $definition = [$definition];
        }

        $this->definitions[$locale][$domain][$key] = $definition;

        return $this;
    }
}
