<?php

namespace atk4\core;

trait QuickExceptionTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_quickExceptionTrait = true;

    public $default_exception = 'atk4\core\Exception';

    public function exception()
    {
    }
}
