<?php

namespace atk4\core;

/**
 * Generic PHPUnit exception wrapper for ATK4 repos.
 */
class PHPUnit7_AgileExceptionWrapper extends \PHPUnit\Framework\Exception
{
    /** @var \Exception Previous exception */
    public $previous;

    /**
     * Constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $this->previous = $previous;
        parent::__construct($message, $code, $previous);
    }
}
