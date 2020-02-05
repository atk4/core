<?php

namespace atk4\core;

/**
 * @deprecated use HasName instead.
 */
trait NameTrait
{
    use Concerns\HasName;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_nameTrait = true;
}
