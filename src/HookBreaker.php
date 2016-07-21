<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\core;

/**
 * Special exception for HookTrait->breakHook method.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class HookBreaker extends \Exception
{
    public $return_value = null;

    public function __construct($rv)
    {
        $this->return_value = $rv;
    }
}
