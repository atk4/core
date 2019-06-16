<?php

namespace atk4\core;

interface TranslatorInterface
{
    /**
     * Get the translation of a message in the correct plural form
     *
     * @param string      $message The text to translate (always singular)
     * @param integer     $count   The counter used to evaluate the plural
     * @param string|null $context The context if exists
     *
     * @return string
     */
    public function translate(string $message, int $count = 1, ?string $context = NULL): string;
}
