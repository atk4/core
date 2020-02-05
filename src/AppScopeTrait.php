<?php

namespace atk4\core;

/**
 * @deprecated use HasAppScope instead
 *
 */
trait AppScopeTrait
{
    use Concerns\HasAppScope;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_appScopeTrait = true;
}
