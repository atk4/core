<?php

namespace atk4\core;

/**
 * @deprecated use IsTrackable instead.
 */
trait TrackableTrait
{
    use Concerns\IsTrackable;
        
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_trackableTrait = true;    
}
