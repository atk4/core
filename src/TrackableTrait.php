<?php

namespace atk4\core;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
trait TrackableTrait {

    public $_trackableTrait = true;

    public $name;
    public $short_name;
    public $owner;

    function getDesiredName(){
        return get_class($this);
    }

    function destroy(){
        if (
            isset($this->owner) &&
            $this->owner->_containerTrait
        ) {
            $this->owner->removeElement($this->short_name);
        }
    }
}
