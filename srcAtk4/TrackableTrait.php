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
    use NameTrait;

    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_trackableTrait = true;

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
    public $short_name;

    public function issetOwner(): bool
    {
        return $this->owner !== null;
    }

    public function getOwner(): object
    {
        return $this->owner;
    }

    /**
     * @return static
     */
    public function setOwner(object $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Should be used only when object is cloned.
     *
     * @return static
     */
    public function unsetOwner()
    {
        if (!$this->issetOwner()) {
            throw new Exception('Owner not set');
        }

        $this->owner = null;

        return $this;
    }

    /**
     * If name of the object is omitted then it's naturally to name them
     * after the class. You can specify a different naming pattern though.
     */
    public function getDesiredName(): string
    {
        // can be anything, but better to build meaningful name
        $name = static::class;
        if (strpos($name, 'class@anonymous') === 0) {
            $name = '';
            foreach (class_parents(static::class) as $v) {
                if (strpos($v, 'class@anonymous') !== 0) {
                    $name = $v;

                    break;
                }
            }
            $name .= '@anonymous';
        }

        return trim(preg_replace('~^atk4\\\\[^\\\\]+\\\\|[^0-9a-z\x7f-\xfe]+~s', '_', mb_strtolower($name)), '_');
    }

    /**
     * Removes object from parent, so that PHP's Garbage Collector can
     * dispose of it.
     */
    public function destroy(): void
    {
        if (
            isset($this->owner) &&
            $this->owner->_containerTrait
        ) {
            $this->owner->removeElement($this->short_name);

            // GC remove reference to app is AppScope in use
            if (
                isset($this->app) &&
                ($this->_appScopeTrait ?? false) &&
                ($this->owner->_appScopeTrait ?? false)
            ) {
                $this->app = null;
            }

            // GC : remove reference to owner
            $this->owner = null;
        }
    }
}
