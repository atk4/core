<?php

namespace atk4\core\Concerns;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
trait IsTrackable
{
    use HasName;
    use CanCheckTraits;

    /**
     * Link to (parent) object into which we added this object.
     *
     * @var object
     */
    public $owner;

    /**
     * Name of the object in owner's element array.
     *
     * @var string
     */
    public $short_name = null;

    /**
     * If name of the object is omitted then it's naturally to name them
     * after the class. You can specify a different naming pattern though.
     *
     * @return string
     */
    public function getDesiredName()
    {
        return preg_replace('/.*\\\\/', '', strtolower(get_class($this)));
    }

    /**
     * Removes object from parent, so that PHP's Garbage Collector can
     * dispose of it.
     */
    public function destroy()
    {
        if (isset($this->owner) && $this->dispositionOf($this->owner, IsContainer::class)) {
            $this->owner->removeElement($this->short_name);

            // GC remove reference to app is AppScope in use
            if (isset($this->app) && $this->hasDispositionMatch($this->owner, HasAppScope::class)) {
                $this->app = null;
            }

            // GC : remove reference to owner
            $this->owner = null;
        }
    }
}
