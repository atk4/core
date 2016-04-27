<?php

namespace atk4\core;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
trait TrackableTrait {

    public $name;
    public $short_name;

    function getDesiredName(){
        return get_class($this);
    }

    function destroy(){
        unset($this->owner->elements[$this->short_name]);
    }
}
