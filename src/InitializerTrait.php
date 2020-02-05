<?php

namespace atk4\core;

/**
 * @deprecated use CanInitialize instead. 
 */
trait InitializerTrait
{
    use Concerns\CanInitialize;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_initializerTrait = true;
}
