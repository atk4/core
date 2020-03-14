<?php

namespace atk4\core;

/**
 * Trait StaticAddToTrait.
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
     * A better way to initialize and add new object into parent - more typehinting-friendly.
     *
     * $crud = CRUD::addTo($app, ['displayFields' => ['name']]);
     *   is equivalent to
     * $crud = $app->add(['CRUD', 'displayFields' => ['name']]);
     *   but the first one design pattern is strongly recommended as it supports refactoring.
     *
     * @param object       $parent
     * @param array|string $seed
     *
     * @return static
     */
    public static function addTo(object $parent, $seed = [], ...$add_arguments)
    {
        if (isset($parent->_factoryTrait)) {
            $object = $parent->factory(static::class, $seed);
        } elseif (is_array($seed)) {
            $object = new static(...$seed);
        } else {
            $object = new static($seed);
        }
        $parent->add($object, ...$add_arguments);

        return $object;
    }
}
