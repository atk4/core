<?php


namespace atk4\core;


interface TranslatorInterface
{
    /**
     * called to set the language
     *
     * @param string $ISOCode         Current Language
     * @param string $fallbackISOCode Fallback language in case of missing translations
     */
    public function set(string $ISOCode, string $fallbackISOCode);

    /**
     * Add translation of a specific string
     *
     * @param                   $string
     * @param array<int,string> $translations (int) is the plural count | (string) is translation
     *
     * @throws Exception
     */
    public function addOne(string $string, array $translations);

    /**
     * Return translation of string
     *
     * @param string $string
     *
     * @return string
     */
    public function translate(string $string): string;

    /**
     * Return translation of string in plural form for $number <> 1
     *
     * @param string $string
     * @param        $number
     *
     * @return string
     */
    public function translate_plural(string $string, $number): string;
}