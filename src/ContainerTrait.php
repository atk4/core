<?php

namespace atk4\core;

/**
 * @deprecated use IsContainer instead.
 */
trait ContainerTrait
{
    use Concerns\IsContainer;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_containerTrait = true;
}
