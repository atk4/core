<?php

namespace atk4\core;

/**
 * @deprecated use CanCheckTraits instead
 */
trait DIContainerTrait
{
    use Concerns\HasDIContainer;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_DIContainerTrait = true;
}
