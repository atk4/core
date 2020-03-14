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
     * The new object is checked if it is instance of current class.
     *
     * $crud = CRUD::addTo($app, ['displayFields' => ['name']]);
     *   is equivalent to
     * $crud = $app->add(['CRUD', 'displayFields' => ['name']]);
     *   but the first one design pattern is strongly recommended as it supports refactoring.
     *
     * @param array|string $seed
     *
     * @return static
     */
    public static function addTo(object $parent, $seed = [], array $add_arguments = [])
    {
        if (is_object($seed)) {
            $object = $seed;
        } else {
            if (!is_array($seed)) {
                if (!is_scalar($seed)) { // allow single element seed but prevent bad usage
                    throw (new Exception(['Seed must be an array or a scalar']))
                            ->addMoreInfo('seed_type', gettype($seed));
                }

                $seed = [$seed];
            }

            if (isset($parent->_factoryTrait)) {
                $object = $parent->factory(static::class, $seed);
            } else {
                $object = new static(...$seed);
            }
        }

        // check if object is instance of this class
        if (!($object instanceof static)) {
            throw (new Exception(['Seed class name is not a subtype of the current class']))
                    ->addMoreInfo('seed_class', get_class($object))
                    ->addMoreInfo('current_class', static::class);
        }

        // add to parent
        $parent->add($object, ...$add_arguments);

        return $object;
    }

    /**
     * Same as addTo(), but the first element of seed specifies a class name instead of static::class.
     *
     * @param array|string $seed The first element specifies a class name, other element are seed
     *
     * @return static
     */
    public static function addToWithClassName(object $parent, $seed = [], array $add_arguments = [])
    {
        if (is_object($seed)) {
            $object = $seed;
        } else {
            if (!is_array($seed)) {
                if (!is_scalar($seed)) { // allow single element seed but prevent bad usage
                    throw (new Exception(['Seed must be an array or a scalar']))
                            ->addMoreInfo('seed_type', gettype($seed));
                }

                $seed = [$seed];
            }

            if (isset($parent->_factoryTrait)) {
                $object = $parent->factory($seed);
            } else {
                $cl = reset($seed);
                unset($seed[key($seed)]);
                $object = new $cl(...$seed);
            }
        }

        static::addTo($parent, $object, false, $add_arguments);
    }
}
