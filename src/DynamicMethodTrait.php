<?php

namespace atk4\core;

/**
 * This trait makes it possible for you to add dynamic methods
 * into your object.
 */
trait DynamicMethodTrait
{
    use Concerns\HasDynamicMethods;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_dynamicMethodTrait = true;
}
