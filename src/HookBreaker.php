<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\core;

/**
 * Special exception for HookTrait->breakHook method.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class HookBreaker extends Exception
{
    /**
     * @var mixed
     */
    public $return_value = null;

    /**
     * @param mixed $return_value
     */
    public function __construct($return_value)
    {
        $this->return_value = $return_value;
    }
}
