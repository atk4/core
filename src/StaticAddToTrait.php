<?php

namespace atk4\core;

/**
 * Trait StaticAddToTrait
 * @package atk4\core
 */
trait StaticAddToTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_staticAddToTrait = true;

    /**
     * A better way to initialize and add new object into parent - more typehinting-friendly
     *
     * $crud = CRUD::addTo($app, ['displayFields'=>['name']]);
     *
     * is equivalent to
     *
     *
     * //
     * @param object $parent
     * @param array $seed
     *
     * @return self
     */
    public static function addTo(object $parent, $seed = [], ...$add_arguments) {
        if (isset($parent->_factoryTrait)) {
            $object = $parent->factory(static::class, $seed);
        } else {
            $object = new static($seed);
        }
        $parent->add($object, ...$add_arguments);
        return $object;
    }
}
