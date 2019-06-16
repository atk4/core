<?php

namespace atk4\core;

interface TranslatorInterface
{
    /**
     * Get the translation of a message
     *
     * @param  string      $message The text to translate
     * @param  string|null $context The context if exists
     *
     * @return string
     */
    public function translate(string $message, ?string $context) :string;

    /**
     * Get the translation of a message in the correct plural form
     *
     * @param  integer     $count   The counter used to evaluate the plural
     * @param  string      $message The text to translate (in singular)
     * @param  string|null $context The context if exists
     *
     * @return string
     */
    public function translatePlural(int $count, string $message, ?string $context) :string;
}
