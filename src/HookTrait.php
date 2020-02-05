<?php

namespace atk4\core;

/**
 * @deprecated use HasHooks instead.
 */
trait HookTrait
{
    use Concerns\HasHooks;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_hookTrait = true;
}
