<?php

namespace atk4\core\Concerns;

trait CanThrowQuickException
{
    /**
     * Default exception class name.
     *
     * @var string
     */
    public $default_exception = 'atk4\core\Exception';

    /**
     * Calls exception.
     */
    public function exception()
    {
    }
}
