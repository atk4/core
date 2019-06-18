<?php

namespace atk4\core;

/**
 * If a class use this trait, string can be translated calling method translate.
 */
trait TranslatableTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_translatableTrait = true;

    /**
     * Get the translation of a message in the correct plural form
     *
     * @param string $string  the text to translate
     * @param int    $count   the counter used to evaluate the plural
     * @param string $context the context domain if exists
     *
     * @return string
     */
    public function _(string $string, int $count = 1, string $context = 'atk4'): string
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($string, $count, $context);
        }

        return $string;
    }

    /**
     * Get the translation of a message in the correct plural form with contaxt domain
     *
     * @param string $string  the text to translate
     * @param string $context the context domain if exists
     * @param int    $count   the counter used to evaluate the plural
     *
     * @return string
     */
    public function _d(string $string, string $context = 'atk4', int $count = 1): string
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($string, $count, $context);
        }

        return $string;
    }
}
