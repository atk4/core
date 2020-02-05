<?php

namespace atk4\core;

/**
 * @deprecated use HasFactory instead.
 *
 */
trait FactoryTrait
{
    use Concerns\HasFactory;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_factoryTrait = true;
}
