<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * Trait StaticAddToTrait.
 *
 * Intended to be always used with DiContainerTrait trait.
 */
trait StaticAddToTrait
{
    // use DiContainerTrait; // uncomment once PHP7.2 support is dropped

    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_staticAddToTrait = true;

    private static function _addTo_add(object $parent, object $object, array $addArgs, bool $skipAdd = false): void
    {
        if (!$skipAdd) {
            $parent->add($object, ...$addArgs);
        }
    }

    /**
     * Initialize and add new object into parent. The new object is asserted to be an instance of current class.
     *
     * The best, typehinting-friendly, way to create an object if it should be immediately
     * added to a parent (otherwise use fromSeed() method).
     *
     * $crud = Crud::addTo($app, ['displayFields' => ['name']]);
     *   is equivalent to
     * $crud = $app->add(['Crud', 'displayFields' => ['name']]);
     *   but the first one design pattern is strongly recommended as it supports refactoring.
     *
     * @param array|string $seed
     *
     * @return static
     */
    public static function addTo(object $parent, $seed = [], array $addArgs = [], bool $skipAdd = false)// :static supported by PHP8+
    {
        if (!is_object($seed) && !is_array($seed)) {
            if (!is_scalar($seed)) { // allow single element seed but prevent bad usage
                throw (new Exception('Seed must be an array, a scalar or an object'))
                    ->addMoreInfo('seed_type', gettype($seed));
            }

            $seed = [$seed];
        }

        $object = static::fromSeed([static::class], $seed);

        static::_addTo_add($parent, $object, $addArgs, $skipAdd);

        return $object;
    }

    /**
     * Same as addTo(), but the first element of seed specifies a class name instead of static::class.
     *
     * @param array|string|object $seed the first element specifies a class name, other elements are seed
     *
     * @return static
     */
    public static function addToWithCl(object $parent, $seed = [], array $addArgs = [], bool $skipAdd = false)// :static supported by PHP8+
    {
        $object = static::fromSeed($seed);

        static::_addTo_add($parent, $object, $addArgs, $skipAdd);

        return $object;
    }

    /**
     * Same as addToWithCl(), but the new object is not asserted to be an instance of this class.
     *
     * @param array|string|object $seed the first element specifies a class name, other elements are seed
     *
     * @return static
     */
    public static function addToWithClUnsafe(object $parent, $seed = [], array $addArgs = [], bool $skipAdd = false)// :self is too strict with unsafe behaviour
    {
        $object = static::fromSeedUnsafe($seed);

        static::_addTo_add($parent, $object, $addArgs, $skipAdd);

        return $object;
    }
}
