<?php

namespace atk4\core;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
trait TrackableTrait
{
    /**
     * Check this property to see if TrackableTrait is present
     * in the object.
     *
     * @var string
     */
    public $_trackableTrait = true;

    /**
     * Link to object into which we added this object.
     *
     * @var AbstractObject
     */
    public $owner;

    /**
     * Unique object name.
     *
     * @var string
     */
    public $name;

    /**
     * Name of the object in owner's element array.
     *
     * @var string
     */
    public $short_name;

    /**
     * If name of the object is ommitted then it's naturally to name them
     * after the class. You can specify a different naming pattern though.
     */
    public function getDesiredName()
    {
        return str_replace('\\', '_', strtolower(get_class($this)));
    }

    /**
     * Removes object from parent, so that PHP's Garbage Collector can
     * dispose of it.
     */
    public function destroy()
    {
        if (
            isset($this->owner) &&
            $this->owner->_containerTrait
        ) {
            $this->owner->removeElement($this->short_name);
        }
    }
}
