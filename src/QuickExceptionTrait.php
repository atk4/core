<?php

declare(strict_types=1);

namespace atk4\core;

trait QuickExceptionTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_quickExceptionTrait = true;

    /**
     * Default exception class name.
     *
     * @var string
     */
    public $default_exception = Exception::class;

    /**
     * Calls exception.
     *
     * @TODO NOT IMPLEMENTED
     */
    public function exception()
    {
    }
}
