<?php

namespace atk4\core;

trait StaticAddToTrait
{
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
        $object = new static($seed);
        $parent->add($object, ...$add_arguments);
        return $object;
    }
}
