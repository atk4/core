<?php

declare(strict_types=1);

namespace Atk4\Core;

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

    /**
     * Do not call directly.
     */
    public function invokeInit(): void
    {
        // assert init() method is not declared as public, ie. not easily directly callable by the user
        if ((new \ReflectionMethod($this, 'init'))->getModifiers() & \ReflectionMethod::IS_PUBLIC) {
            throw new Exception('Init method must have protected visibility');
        }

        $this->init();
    }
}
