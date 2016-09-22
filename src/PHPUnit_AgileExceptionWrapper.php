<?php

namespace atk4\core;

class PHPUnit_AgileExceptionWrapper extends \PHPUnit_Framework_Exception
{
    public $previous;

    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $previous = $previous;
        parent::__construct($message, $code, $previous);
    }
}
