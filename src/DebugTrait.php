<?php

namespace atk4\core;

use Psr\Log\LogLevel;

/**
 * @deprecated use CanDebug instead
 *
 */
trait DebugTrait
{
    use Concerns\CanDebug;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_debugTrait = true;
    
}
