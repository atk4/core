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
    protected $returnValue;

    /**
     * @param mixed $returnValue
     */
    public function __construct($returnValue)
    {
        parent::__construct();
        $this->returnValue = $returnValue;
    }

    /**
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }
}
