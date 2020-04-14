<?php

namespace atk4\core;

/**
 * Generic PHPUnit exception wrapper for ATK4 repos.
 */
class PHPUnit7_AgileExceptionWrapper extends \PHPUnit\Framework\Exception
{
    /** @var \Throwable Previous exception */
    public $previous;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->previous = $previous;
        parent::__construct($message, $code, $previous);
    }
}
