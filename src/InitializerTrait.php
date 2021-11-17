<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * Object with this trait will have it's init() method executed
 * automatically when initialized through add().
 */
trait InitializerTrait
{
    /** @var bool */
    private $_initialized = false;

    /**
     * Initialize object. Always call parent::init(). Do not call directly.
     */
    protected function init(): void
    {
        if ($this->isInitialized()) {
            throw (new Exception('Object already initialized'))
                ->addMoreInfo('this', $this);
        }
        $this->_initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->_initialized;
    }

    public function assertIsInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new Exception('Object was not initialized');
        }
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

        $this->assertIsInitialized();
    }
}
