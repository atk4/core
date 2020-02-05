<?php

declare(strict_types=1);

namespace atk4\core;

/**
 * @deprecated use CanTranslate instead.
 */
trait TranslatableTrait
{
    use Concerns\CanTranslate;
    
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     * 
     * @deprecated use automated trait detection with CanCheckTraits
     * 
     */
    public $_translatableTrait = true;    
}
