<?php

namespace atk4\core;

/**
 * @deprecated use UsesConfig instead
 */
trait ConfigTrait
{
    use Concerns\UsesConfig;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_configTrait = true;    
}
