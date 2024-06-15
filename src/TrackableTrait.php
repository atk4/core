<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
trait TrackableTrait
{
    /** @var QuietObjectWrapper<object>|null Link to (owner) object into which we added this object. */
    private ?QuietObjectWrapper $_owner = null;

    /** @var non-falsy-string Name of the object in owner's element array. */
    public string $shortName;

    public function issetOwner(): bool
    {
        return $this->_owner !== null;
    }

    public function getOwner(): object
    {
        $owner = $this->_owner;
        if ($owner === null) {
            throw new Exception('Owner is not set');
        }

        return $owner->get();
    }

    /**
     * @return $this
     */
    public function setOwner(object $owner)
    {
        if ($this->issetOwner()) {
            throw new Exception('Owner is already set');
        }

        $this->_owner = new QuietObjectWrapper($owner);

        return $this;
    }

    /**
     * Should be used only when object is cloned.
     *
     * @return $this
     */
    public function unsetOwner()
    {
        $this->getOwner(); // assert owner is set

        $this->_owner = null;

        return $this;
    }

    /**
     * If name of the object is omitted then it's naturally to name them
     * after the class. You can specify a different naming pattern though.
     */
    public function getDesiredName(): string
    {
        // can be anything, but better to build meaningful name
        $name = get_debug_type($this);

        return trim(preg_replace('~^atk4\\\[^\\\]+\\\|[^0-9a-z\x7f-\xfe]+~s', '_', mb_strtolower($name)), '_');
    }

    /**
     * Removes object from parent, so that PHP's Garbage Collector can
     * dispose of it.
     */
    public function destroy(): void
    {
        if ($this->_owner !== null && TraitUtil::hasContainerTrait($this->_owner->get())) {
            $this->_owner->get()->removeElement($this->shortName);

            // GC remove reference to app is AppScope in use
            if (TraitUtil::hasAppScopeTrait($this) && $this->issetApp()) {
                $this->_app = null; // @phpstan-ignore property.notFound
            }

            // GC remove reference to owner
            $this->_owner = null;
        }
    }
}
