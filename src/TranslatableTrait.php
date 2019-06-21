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
     * Get the translation of a message in the correct plural form.
     *
     * @param string      $message
     * @param array|null  $args
     * @param string|null $context
     *
     * @return string
     */
    public function _(string $message,?array $args=null,?string $context=null)
    {
        if (isset($this->app) && method_exists($this->app, '_')) {
            return $this->app->_($message,$args,$context);
        }

        return $message;
    }
}
