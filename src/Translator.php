<?php


namespace atk4\core;


final class Translator implements TranslatorInterface
{
    use ConfigTrait;

    private $translation = [];

    /** @var string */
    private $language;

    /** @var string */
    private $fallback;

    /** @var string|null */
    private $translation_path;
    /** @var string */
    private $translation_format;

    public function getLanguage()
    {
        return $this->language;
    }

    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Translator constructor.
     *
     * @param string|null $translation_path root path of translations
     * @param string $format
     */
    public function __construct(string $translation_path = null, string $format = 'php-inline')
    {
        $this->translation_path   = $translation_path;
        $this->translation_format = $format;
    }

    /**
     * called to set the language
     *
     * @param string $ISOCode         Current Language
     * @param string|null $fallbackISOCode Fallback language in case of missing translations
     *
     * @throws Exception
     */
    public function set(string $ISOCode, string $fallbackISOCode = null)
    {
        $this->language = $ISOCode;

        $this->fallback = $fallbackISOCode ?? $this->language;

        // if no base path is specified don't load
        if(!$this->translation_path)
        {
            return;
        }

        $ext = 'php';

        switch($this->translation_format)
        {
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
            $this->translation_path . DIRECTORY_SEPARATOR . $this->language . '.' . $ext,
            $this->translation_path . DIRECTORY_SEPARATOR . $this->fallback . '.' . $ext
        ];

        $language_files = array_unique($language_files);

        $this->readConfig($language_files,$this->translation_format);

        $this->translation = $this->config;
    }

    /**
     * Add translation of a specific string
     *
     * @param                   $string
     * @param array<int,string> $translations (int) is the plural count | (string) is translation
     *
     * @throws Exception
     */
    public function addOne(string $string, array $translations)
    {
        if (array_key_exists($string, $this->translation)) {
            throw new Exception('Translation already exists');
        }

        $this->translation[$string] = $translations;
    }

    /**
     * Return translation of string
     *
     * @param string $string
     *
     * @return string
     * @throws Exception
     */
    public function translate(string $string): string
    {
        return $this->translate_plural($string, 1);
    }

    /**
     * Return translation of string in plural form for $number <> 1
     *
     * @param string $string
     * @param        $number
     *
     * @return string
     * @throws Exception
     */
    public function translate_plural(string $string, $number): string
    {
        // check presence of key
        $trans = $this->translation[$string] ?? false;

        // if not present return string
        if (false === $trans) {
            return $string;
        }

        // if present but empty raise exception
        if (empty($trans)) {
            throw new Exception('Translation is present but is empty');
        }

        if(count($trans) === 1)
        {
            return current($this->translation[$string]);
        }

        if ($number > 1) {
            $number = min($number, max(array_keys($trans)));
        }

        return $this->translation[$string][(int)$number] ?? $string;
    }
}