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
     * @param string $message the text to translate
     * @param int    $count   the counter used to evaluate the plural
     *
     * @return string
     */
    public function _(string $message, int $count = 1)
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($message, $count ?? 1);
        }

        return $message;
    }

    /**
     * Get the translation of a message in the correct plural form
     *
     * @param string   $message the text to translate
     * @param string   $domain  the context domain if exists
     * @param int|null $count   the counter used to evaluate the plural
     *
     * @return string
     */
    public function _d(string $message, string $domain = 'atk4', int $count = 1)
    {
        if (isset($this->app) && method_exists($this->app, '_d')) {
            return $this->app->_d($message, $domain, $count);
        }

        return $message;
    }
}

//
// $this->_m('test %s %s', null, ['test','testing',['test',null,['test']]])