<?php

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
    public $return_value;

    /**
     * @param mixed $return_value
     */
    public function __construct($return_value)
    {
        parent::__construct();
        $this->return_value = $return_value;
    }
}
