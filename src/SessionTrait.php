<?php

namespace atk4\core;

/**
 * @deprecated use UsesSession instead.
 */
trait SessionTrait
{
    use Concerns\UsesSession;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_sessionTrait = true;
}
