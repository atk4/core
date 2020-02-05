<?php

namespace atk4\core\Concerns;

use atk4\core\Exception;

/**
 * Object with this trait will have it's init() method executed
 * automatically when initialized through add().
 */
trait CanInitialize
{
    /**
     * To make sure you have called parent::init() properly.
     *
     * @var bool
     */
    public $_initialized = false;

    /**
     * Initialize object. Always call parent::init(). Do not call directly.
     */
    public function init()
    {
        if ($this->_initialized) {
            throw new Exception(['Attempting to initialize twice', 'this'=>$this]);
        }
        $this->_initialized = true;
    }
}
