<?php

namespace atk4\core;

/**
 * A class with this trait will have setDefaults() method that can
 * be passed list of default properties.
 *
 * $view->setDefaults(['ui' => 'segment']);
 *
 * Typically you would want to do that inside your constructor. The
 * default handling of the properties is:
 *
 *  - only apply properties that are defined
 *  - only set property if it's current value is null
 *  - ignore defaults that have null value
 *  - if existing property and default have array, then both arrays will be merged
 *
 * Several classes may opt to extend setDefaults, for example in Agile UI
 * setDefaults is extended to support classes and content:
 *
 * $segment->setDefaults(['Hello There', 'red', 'ui'=>'segment']);
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
     * developer to pass Dependency Injector Container.
     *
     * @param array $properties
     * @param bool  $passively  if true, existing non-null argument values will be kept
     */
    public function setDefaults($properties = [], $passively = false)
    {
        if ($properties === null) {
            $properties = [];
        }

        foreach ($properties as $key => $val) {
            if (!is_numeric($key) && property_exists($this, $key)) {
                if ($passively && $this->$key !== null) {
                    continue;
                }

                if ($val !== null) {
                    $this->$key = $val;
                }
            } else {
                $this->setMissingProperty($key, $val);
            }
        }
    }

    /**
     * Sets object property.
     * Throws exception.
     *
     * @param mixed $key
     * @param mixed $value
     * @param bool  $strict
     */
    protected function setMissingProperty($key, $value)
    {
        // ignore numeric properties by default
        if (is_numeric($key)) {
            return;
        }

        throw new Exception([
            'Property for specified object is not defined',
            'object'  => $this,
            'property'=> $key,
            'value'   => $value,
        ]);
    }
}
