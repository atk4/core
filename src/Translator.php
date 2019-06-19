<?php

namespace atk4\core;

use phpDocumentor\Reflection\Types\Boolean;

class Translator implements TranslatorInterface
{
    use ConfigTrait;

    /**
     * ISOCode of the main language.
     *
     * @var string
     */
    protected $language;

    /**
     * ISOCode of the fallback language.
     *
     * @var string|null
     */
    protected $fallback = 'en';

    /**
     * Array where Translation will be stored.
     *
     * @var array
     */
    protected $translations = [];

    /**
     * Raise exception if format of translation is not correct
     *
     * @var bool
     */
    public $raise_bad_format_exception = false;

    /**
     * @param string      $language Primary Language
     * @param string|null $fallback Fallback Language
     */
    public function __construct(string $language, ?string $fallback = null)
    {
        $this->language = $language;
        $this->fallback = $fallback ?? $this->fallback;
    }

    /**
     * Get Primary Language
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get Fallback Language
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * Add one string and his translated plural forms to a domain context
     *
     * @param string $string       string to be translated
     * @param array  $translations plural forms translation
     * @param string $context      the context domain if exists
     * @param bool   $replace      replace translation if exists
     *
     * @throws Exception
     */
    public function addOne(string $string, array $translations, string $context = 'atk4', bool $replace = false)
    {
        if ($replace) {
            $this->translations[$context][$string] = $translations;
            return;
        }

        $trans_context_string = $this->translations[$context][$string] ?? null;

        if($trans_context_string)
        {
            throw new \atk4\core\Exception('Translation already exists');
        }

        $this->addOne($string, $translations, $context, true);
    }

    /**
     * Add a translation from a folder with a specific format.
     *
     * @param string $path   full path to translation files
     * @param string $format ConfigTrait format can be : php | php-inline | json | yaml
     *
     * @throws Exception
     */
    public function addFromFolder(string $path, string $format = 'php-inline')
    {
        $ext = false;
        // if Translation ext is not recognized throw exception
        switch ($format) {

            case 'php':
            case 'php-inline':
                $ext = 'php';
                break;

            case 'json':
                $ext = 'json';
                break;

            case 'yaml':
                $ext = 'yml';
                break;
        }

        $fallback = [];

        $language = $this->getTranslationsFromFile(
            $path . DIRECTORY_SEPARATOR . $this->language . '.' . $ext,
            $format
        );

        if ($this->fallback !== $this->language) {

            $fallback = $this->getTranslationsFromFile(
                $path . DIRECTORY_SEPARATOR . $this->fallback . '.' . $ext,
                $format
            );
        }

        $this->translations = array_replace_recursive($this->translations, $fallback, $language);
    }

    /**
     * @param string $file   full path to file
     * @param string $format ConfigTrait format
     *
     * @return array
     * @throws Exception
     */
    private function getTranslationsFromFile(string $file, string $format): array
    {
        // need to check here for existence
        // to exclude exception for existence in ConfigTrait
        if (!file_exists($file)) {
            return [];
        }

        $this->readConfig($file, $format);
        $translations = $this->config;

        // empty stored config
        $this->config = [];

        return $translations;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function translate(string $string, ?int $count = 1, ?string $context = NULL): string
    {
        // check presence of string
        $trans = $this->translations[$context][$string] ?? false;

        // if not present return string
        if (false === $trans) {
            return $string;
        }

        // this a sort of lazy check of consistency
        // check is here to avoid checking of every string when loading translation files
        if (empty($trans)) {
            if ($this->raise_bad_format_exception) {
                throw new \atk4\core\Exception('Translation is present but is empty');
            }
            return $string;
        }

        // if declared without plurals as string -> normalize to single array
        if (is_string($trans)) {
            return $trans;
        }

        if (count($trans) === 1) {
            return current($trans);
        }

        if ($count > 1) {
            $count = min($count, max(array_keys($trans)));
        }

        return $trans[(int)$count] ?? $string;
    }
}
