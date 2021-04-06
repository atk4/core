<?php

declare(strict_types=1);

namespace atk4\core;

/**
 * If class implements that interface and is added into "Container",
 * then container will keep track of it. This method can also
 * specify desired name of the object.
 */
interface ITrackable
{
    use NameTrait;

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
            // $name .= '@anonymous';
        }

        return trim(preg_replace('~^.+\\\\|[^0-9a-z\x7f-\xfe]+~s', '_', mb_strtolower($name)), '_');
    }

    /**
     * Removes object from parent, so that PHP's Garbage Collector can
     * dispose of it.
     */
    public function destroy(): void
    {
        if (isset($this->owner) && TraitUtil::hasContainerTrait($this->owner)) {
            $this->owner->removeElement($this->short_name);

            // GC remove reference to app is AppScope in use
            if (
                isset($this->app) &&
                TraitUtil::hasAppScopeTrait($this) &&
                TraitUtil::hasAppScopeTrait($this->owner)
            ) {
                $this->app = null;
            }

            // GC : remove reference to owner
            $this->owner = null;
        }
    }
}
