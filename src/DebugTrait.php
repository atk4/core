<?php

namespace atk4\core;

trait DebugTrait {
    public $debug=false;

    function debug(){
        $this->debug=true;
    }
}
