<?php

declare(strict_types=1);

namespace atk4\core;

/**
 * Special exception for HookTrait->breakHook method.
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
