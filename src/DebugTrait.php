<?php

namespace atk4\core;

trait DebugTrait
{
    public $debug = false;

    public function debug()
    {
        $this->debug = true;
    }
}
