<?php

namespace atk4\core\Concerns;

use atk4\core\Utils;

trait CanCheckTraits
{
    /**
     * Checks if the current object has a trait.
     * 
     * @param string                   $trait
     * @param Object|string|array|null $object
     * 
     * @return boolean
     */
    public function disposition($hasTrait)
    {
        return $this->dispositionOf($this, $hasTrait);
    }
    
    /**
     * Checks if the object has a trait.
     * If array of objects supplied method checks if all of them have the trait
     *
     * @param Object|string $object
     * @param string        $hasTrait
     * 
     * @return boolean
     */
    public function dispositionOf($object, $hasTrait)
    {
        if (!$object) {
            return false;
        }
        
        if (is_array($object)) {
            $ret = true;
            foreach ($object as $instance) {
                $ret &= $this->dispositionOf($instance, $hasTrait);
            }
            
            return $ret;
        }
        
        return in_array($hasTrait, Utils::classUsesRecursive($object));
    }
    
    /**
     * Checks if suppied trait is available in the object and in the current object
     * 
     * @param string        $trait
     * @param Object|string $withObject
     * 
     * @return boolean
     */
    public function hasDispositionMatch($withObject, $onTrait)
    {
        return $this->dispositionOf([$this, $withObject], $onTrait);
    }
}
