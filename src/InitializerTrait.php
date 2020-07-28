<?php

declare(strict_types=1);

namespace atk4\core;

/**
 * Object with this trait will have it's init() method executed
 * automatically when initialized through add().
 */
trait InitializerTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_initializerTrait = true;

    /**
     * To make sure you have called parent::init() properly.
     *
     * @var bool
     */
    public $_initialized = false;

    /**
     * Initialize object. Always call parent::init(). Do not call directly.
     */
    protected function init(): void
    {
        if ($this->_initialized) {
            throw (new Exception('Attempting to initialize twice'))
                ->addMoreInfo('this', $this);
        }
        $this->_initialized = true;
    }

//    public function beforeInit(): void
//    {
//    }
//
//    public function afterInit(): void
//    {
//    }

    /**
     * Do not call directly.
     */
    public function invokeInit(): void
    {
//        $this->beforeInit();
        $this->init();
//        $this->afterInit();
    }
}
