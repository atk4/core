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
     *
     * @return string
     */
    public function _(string $string, int $count = 1): string
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($string, $count, 'atk4');
        }

        return $string;
    }

    /**
     * Get the translation of a message in the correct plural form with contaxt domain
     *
     * @param string $context the context domain if exists
     * @param string $string  the text to translate
     * @param int    $count   the counter used to evaluate the plural
     *
     * @return string
     */
    public function _d(string $context, string $string, int $count = 1): string
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($string, $count, $context);
        }

        return $string;
    }

    /**
     * Helper to sprintf a string.
     *
     * @param string        $string
     * @param array<array>  $args
     *
     * @return string
     */
    public function _m(string $string, array $args)
    {
        $translated_args = [];
        $translated_args[] = $this->_($string);
        $this->_processTranslatedArray($translated_args, $args);

        return sprintf(...$translated_args);
    }

    /**
     * Helper to sprintf a string.
     *
     * @param string $context
     * @param string $string
     * @param mixed  ...$args
     *
     * @return string
     */
    public function _md(string $context, string $string, array $args)
    {
        $translated_args = [];
        $translated_args[] = $this->_d($context, $string);
        $this->_processTranslatedArray($translated_args, $args);

        return sprintf(...$translated_args);
    }

    private function _processTranslatedArray(&$translated_args, $args)
    {
        foreach ($args as $sub_args) {

            if (!is_array($sub_args)) {
                $sub_args = [$sub_args];
            }

            switch(count($sub_args))
            {
                case 1:
                    $translated_args[] = $this->_(...$sub_args);
                    break;
                case 2:
                    if(is_numeric($sub_args[1])) {
                        $translated_args[] = $this->_(...$sub_args);
                    } else {
                        $translated_args[] = $this->_d(...$sub_args);
                    }
                    break;
                case 3:
                    $translated_args[] = $this->_d(...$sub_args);
                    break;
            }
        }
    }
}
