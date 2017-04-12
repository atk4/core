<?php

namespace atk4\core;

/**
 * A class with this trait will have setProperties() method that can
 * be passed list of default properties. 
 *
 * $view->setProperties(['ui' => 'segment']);
 *
 * Typically you would want to do that inside your constructor. The
 * default handling of the properties is:
 *
 *  - only apply properties that are defined
 *  - only set property if it's current value is null
 *  - ignore defaults that have null value
 *  - if existing property and default have array, then both arrays will be merged
 *
 * Several classes may opt to extend setProperties, for example in Agile UI
 * setProperties is extended to support classes and content:
 *
 * $segment->setPropertes(['Hello There', 'red', 'ui'=>'segment']);
 *
 * WARNING: Do not use this trait unless you have a lot of properties
 * to inject. Also follow the guidelines on
 * https://github.com/atk4/ui/wiki/Object-Constructors
 *
 * Relying on this trait excessively may cause anger management issues to
 * some code reviewers.
 */
trait DIContainerTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_DIContainerTrait = true;

    /**
     * Call from __construct() to initialize the properties allowing
     * developer to pass Dependency Inector Container
     *
     * @param array $properties
     * @param boolean $stric - should we raise exceptions?
     */
    public function setProperties($properties = [], $strict = false)
    {

        if ($properties === null) {
            $properties = [];
        }

        foreach ($properties as $key => $val) {
            if (property_exists($this, $key)) {
                if (is_array($val)) {
                    $this->$key = array_merge(isset($this->$key) && is_array($this->$key) ? $this->$key : [], $val);
                } elseif ($val !== null) {
                    $this->$key = $val;
                }
            } else {
                $this->setMissingProperty($key, $val, $strict);
            }
        }
    }

    public function setMissingProperty($key, $value, $strict = false)
    {
        if ($strict) {
            throw new Exception([
                'Property for specified default is not defined', 
                'object'=>$this, 
                'property'=>$key,
                'value'=>$value
            ]);
        }
    }
}
