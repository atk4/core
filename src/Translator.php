<?php

namespace atk4\core;

class Translator implements TranslatorInterface
{
    use ConfigTrait;

    /**
     * ISOCode of the main language.
     *
     * @var string
     */
    public $language;

    /**
     * ISOCode of the fallback language.
     *
     * @var string
     */
    public $fallback;

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
     * Set primary language ISO Code
     *
     * @param string      $language
     *
     * @param string|null $fallback
     *
     * @return string
     */
    public function setLanguage(string $language,?string $fallback = null)
    {
        $this->language = $language;
        $this->fallback = $fallback ?? false;
    }

    /**
     * Add one string and his translated plural forms to a domain context
     *
     * @param string $string        string to be translated
     * @param array  $translations  plural forms translation
     * @param string $context       the context domain if exists
     *
     * @throws Exception
     */
    public function addOne(string $string, array $translations, string $context = 'atk4')
    {
        if (array_key_exists($string, $this->translations)) {
            throw new \atk4\core\Exception('Translation already exists');
        }

        $this->translations[$context][$string] = $translations;
    }

    /**
     * Add a translation from a folder with a specific format.
     *
     * @param string $path      full path to translation files
     * @param string $format    ConfigTrait format can be : php | php-inline | json | yaml
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

        $language = $this->getTranslationsFromFile($path.DIRECTORY_SEPARATOR.$this->language.'.'.$ext ,$format);

        if($this->fallback)
        {
            $fallback = $this->getTranslationsFromFile($path.DIRECTORY_SEPARATOR.$this->fallback.'.'.$ext ,$format);
        }

        $this->translations  = array_replace_recursive($this->translations, $fallback, $language);
    }

    /**
     * @param string $file      full path to file
     * @param string $format    ConfigTrait format
     *
     * @return array
     * @throws Exception
     */
    private function getTranslationsFromFile(string $file, string $format) : array
    {
        // need to check here for existence
        // to exclude exception for existence in ConfigTrait
        if(!file_exists($file))
        {
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
            if($this->raise_bad_format_exception) {
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

        return $trans[(int) $count] ?? $string;
    }
}
