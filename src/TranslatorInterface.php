<?php

namespace atk4\core;

interface TranslatorInterface
{
    /**
     * Get ISOCode of Main Language.
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Get ISOCode of Fallback Language.
     *
     * @return string
     */
    public function getFallback(): string;

    /**
     * Set the language and fallback.
     *
     * @param string $ISOCode         Current Language
     * @param string $fallbackISOCode Fallback language in case of missing translations
     */
    public function set(string $ISOCode, string $fallbackISOCode);

    /**
     * Add a translation.
     *
     * @param                   $string
     * @param array<int,string> $translations (int) is the plural count | (string) is translation
     *
     * @throws Exception
     */
    public function addOne(string $string, array $translations);

    /**
     * Return translation of string.
     *
     * @param string $string
     *
     * @throws Exception
     *
     * @return string
     */
    public function translate(string $string): string;

    /**
     * Return translation of string in plural form for $number <> 1.
     *
     * @param string $string
     * @param        $number
     *
     * @throws Exception
     *
     * @return string
     */
    public function translate_plural(string $string, $number): string;
}
