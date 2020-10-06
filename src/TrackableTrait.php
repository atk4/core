<?php

declare(strict_types=1);

namespace atk4\core;

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
     * @internal to be removed in Jan 2021, keep until then to prevent wrong assignments
     *
     * @var object
     */
    private $owner;

    /**
     * Link to (parent) object into which we added this object.
     *
     * @var object
     */
    private $_owner;

    /**
     * Name of the object in owner's element array.
     *
     * @var string
     */
    public $short_name;

    /**
     * To be removed in Jan 2021.
     */
    private function assertNoDirectOwnerAssignment(): void
    {
        if ($this->owner !== null) {
            throw new Exception('Owner can not be assigned directly');
        }
    }

    public function issetOwner(): bool
    {
        $this->assertNoDirectOwnerAssignment();

        return $this->_owner !== null;
    }

    public function getOwner(): object
    {
        $this->assertNoDirectOwnerAssignment();

        return $this->_owner;
    }

    /**
     * @return static
     */
    public function setOwner(object $owner)
    {
        $this->assertNoDirectOwnerAssignment();
        if ($this->issetOwner()) {
            throw new Exception('Owner already set');
        }

        $this->_owner = $owner;

        return $this;
    }

    /**
     * Should be used only when object is cloned.
     *
     * @return static
     */
    public function unsetOwner()
    {
        $this->assertNoDirectOwnerAssignment();
        if (!$this->issetOwner()) {
            throw new Exception('Owner not set');
        }

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
        if ($this->_owner !== null && isset($this->_owner->_containerTrait)) {
            $this->_owner->removeElement($this->short_name);

            // GC remove reference to app is AppScope in use
            if (isset($this->_appScopeTrait) && $this->issetApp()) {
                $this->_app = null;
            }

            // GC : remove reference to owner
            $this->_owner = null;
        }
    }
}
