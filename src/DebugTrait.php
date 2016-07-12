<?php

namespace atk4\core;

trait DebugTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_debugTrait = true;

    public $debug = false;

    public function debug()
    {
        $this->debug = true;
    }
}
