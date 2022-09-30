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
    use DiContainerTrait;

    /**
     * @param array<mixed> $addArgs
     */
    private static function _addToAdd(object $parent, object $object, array $addArgs, bool $skipAdd = false): void
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
     * @param array<mixed> $defaults
     * @param array<mixed> $addArgs
     *
     * @return static
     */
    public static function addTo(object $parent, array $defaults = [], array $addArgs = [], bool $skipAdd = false)// :static supported by PHP8+
    {
        $object = static::fromSeed([static::class], $defaults);

        self::_addToAdd($parent, $object, $addArgs, $skipAdd);

        return $object;
    }

    /**
     * Same as addTo(), but the first element of seed specifies a class name instead of static::class.
     *
     * @param array<mixed>|object $seed    the first element specifies a class name, other elements are seed
     * @param array<mixed>        $addArgs
     *
     * @return static
     */
    public static function addToWithCl(object $parent, $seed = [], array $addArgs = [], bool $skipAdd = false)// :static supported by PHP8+
    {
        $object = static::fromSeed($seed);

        self::_addToAdd($parent, $object, $addArgs, $skipAdd);

        return $object;
    }

    /**
     * Same as addToWithCl(), but the new object is not asserted to be an instance of this class.
     *
     * @param array<mixed>|object $seed    the first element specifies a class name, other elements are seed
     * @param array<mixed>        $addArgs
     *
     * @return static
     */
    public static function addToWithClUnsafe(object $parent, $seed = [], array $addArgs = [], bool $skipAdd = false)// :self is too strict with unsafe behaviour
    {
        $object = static::fromSeedUnsafe($seed);

        self::_addToAdd($parent, $object, $addArgs, $skipAdd);

        return $object;
    }
}
