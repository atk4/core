<?php

namespace atk4\core;


/**
 * Object with this trait will have it's init() method executed
 * automatically when initialized through add().
 */
trait InitializerTrait {
    public $_initialized = false;

    function init(){
        $this->_initialized = true;
    }
}
