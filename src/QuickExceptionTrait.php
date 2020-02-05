<?php

namespace atk4\core;

/**
 * @deprecated use CanThrowQuickException instead
 *
 */
trait QuickExceptionTrait
{
    use Concerns\CanThrowQuickException;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_quickExceptionTrait = true;
}
