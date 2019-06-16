<?php

namespace atk4\core;

class Translator implements TranslatorInterface
{
    use ConfigTrait;

    /**
     * Array where Translation will be stored.
     *
     * @var array
     */
    private $translation = [];

    /**
     * ISOCode of the main language.
     *
     * @var string
     */
    private $language;

    /**
     * ISOCode of the fallback language.
     *
     * @var string
     */
    private $fallback;

    /**
     * Path where all translation are stored
     * Can be null because translation can be add at runtime.
     *
     * @var string|null
     */
    private $translation_path;

    /**
     * Format for ConfigTrait to read translations.
     *
     * @var string
     */
    private $translation_format;

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * Translator constructor.
     *
     * @param string|null $translation_path root path of translations
     * @param string      $format           format for ConfigTrait loader
     */
    public function __construct(string $translation_path = null, string $format = 'php-inline')
    {
        $this->translation_path = $translation_path;
        $this->translation_format = $format;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function set(string $ISOCode, string $fallbackISOCode = null)
    {
        $this->language = $ISOCode;

        $this->fallback = $fallbackISOCode ?? $this->language;

        // if no base path is specified don't load
        if (!$this->translation_path) {
            return;
        }

        $ext = 'php';

        switch ($this->translation_format) {
            case 'php':
            case 'php-inline':
                //$ext = 'php';
                break;

            case 'json':
                $ext = 'json';
                break;

            case 'yaml':
                $ext = 'yaml';
                break;
        }

        $language_files = [
            $this->translation_path.DIRECTORY_SEPARATOR.$this->language.'.'.$ext,
            $this->translation_path.DIRECTORY_SEPARATOR.$this->fallback.'.'.$ext,
        ];

        $language_files = array_unique($language_files);

        $this->readConfig($language_files, $this->translation_format);

        $this->translation = $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function addOne(string $string, array $translations, string $domain = 'atk4')
    {
        if (array_key_exists($string, $this->translation)) {
            throw new Exception('Translation already exists');
        }

        $this->translation[$domain][$string] = $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $message, int $count = 1, ?string $context = NULL): string
    {

        // check presence of key
        $trans = $this->translation[$context][$message] ?? false;

        // if not present return string
        if (false === $trans) {
            return $message;
        }

        // if present but empty raise exception
        if (empty($trans)) {
            throw new Exception('Translation is present but is empty');
        }

        if (count($trans) === 1) {
            return current($this->translation[$context][$message]);
        }

        if ($count > 1) {
            $count = min($count, max(array_keys($trans)));
        }

        return $this->translation[$context][$message][(int) $count] ?? $message;
    }
}
