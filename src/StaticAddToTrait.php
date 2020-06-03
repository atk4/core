<?php

declare(strict_types=1);

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
     * Return the argument and check if it is instance of current class. Typehinting-friendly.
     *
     * Best way to annotate object type if it not defined as function parameter or strong typing can not be used.
     *
     * @return static
     */
    public static function checkInstanceOf(object $object)// :static supported by PHP8+
    {
        if (!($object instanceof static)) {
            throw (new Exception('Seed class name is not a subtype of the current class'))
                ->addMoreInfo('seed_class', get_class($object))
                ->addMoreInfo('current_class', static::class);
        }

        return $object;
    }

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
    public static function addTo(object $parent, $seed = [], array $add_args = [], bool $skip_add = false)// :static supported by PHP8+
    {
        if (is_object($seed)) {
            $object = $seed;
        } else {
            if (!is_array($seed)) {
                if (!is_scalar($seed)) { // allow single element seed but prevent bad usage
                    throw (new Exception('Seed must be an array or a scalar'))
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

        return static::_addTo_add($parent, $object, false, $add_args, $skip_add);
    }

    /**
     * @return static
     */
    private static function _addTo_add(object $parent, object $object, bool $unsafe, array $add_args, bool $skip_add = false)
    {
        // check if object is instance of this class
        if (!$unsafe) {
            static::getTyped($object);
        }

        // add to parent
        if (!$skip_add) {
            $parent->add($object, ...$add_args);
        }

        return $object;
    }

    /**
     * Same as addTo(), but the first element of seed specifies a class name instead of static::class.
     *
     * @param array|string $seed The first element specifies a class name, other element are seed
     *
     * @return static
     */
    public static function addToWithClassName(object $parent, $seed = [], array $add_args = [], bool $skip_add = false)// :static supported by PHP8+
    {
        return static::_addToWithClassName($parent, $seed, false, $add_args, $skip_add);
    }

    /**
     * Same as addToWithClassName(), but the new object is not checked if it is instance of this class.
     *
     * @param array|string $seed The first element specifies a class name, other element are seed
     *
     * @return static
     */
    public static function addToWithClassNameUnsafe(object $parent, $seed = [], array $add_args = [], bool $skip_add = false)// :self is too strict with unsafe behaviour
    {
        return static::_addToWithClassName($parent, $seed, true, $add_args, $skip_add);
    }

    /**
     * @return static
     */
    private static function _addToWithClassName(object $parent, $seed, bool $unsafe, array $add_args, bool $skip_add = false)
    {
        if (is_object($seed)) {
            $object = $seed;
        } else {
            if (!is_array($seed)) {
                if (!is_scalar($seed)) { // allow single element seed but prevent bad usage
                    throw (new Exception('Seed must be an array or a scalar'))
                        ->addMoreInfo('seed_type', gettype($seed));
                }

                $seed = [$seed];
            }

            if (!isset($seed[0])) {
                throw new Exception('Class name in seed is not defined');
            }

            if (isset($parent->_factoryTrait)) {
                $object = $parent->factory($seed);
            } else {
                $cl = $seed[0];
                unset($seed[0]);
                $object = new $cl(...$seed);
            }
        }

        return static::_addTo_add($parent, $object, $unsafe, $add_args, $skip_add);
    }
}
