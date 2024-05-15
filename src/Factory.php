<?php

declare(strict_types=1);

namespace Atk4\Core;

class Factory
{
    use WarnDynamicPropertyTrait;

    private static ?Factory $_instance = null;

    protected function __construct()
    {
        // singleton
    }

    final protected static function getInstance(): self
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param array<mixed>|object|null ...$seeds
     *
     * @return ($seeds is object ? object : array<mixed>)
     */
    protected function _mergeSeeds(...$seeds)
    {
        // merge seeds but prefer seed over seed2
        // move numerical keys to the beginning and sort them
        $arguments = [];
        $injection = [];
        $obj = null;
        $beforeObjKeys = null;
        foreach ($seeds as $seedIndex => $seed) {
            if (is_object($seed)) {
                if ($obj !== null) {
                    throw new Exception('Two or more objects specified as seed');
                }

                $obj = $seed;
                if (count($injection) > 0) {
                    $beforeObjKeys = array_flip(array_keys($injection));
                }

                continue;
            } elseif ($seed === null) {
                continue;
            }

            // check seed
            if (!array_key_exists(0, $seed)) {
                // allow this method to be used to merge seeds without class name
            } elseif ($seed[0] === null) {
                // pass
            } elseif (!is_string($seed[0])) {
                throw new Exception('Seed class type (' . get_debug_type($seed[0]) . ') must be string');
            } /*elseif (!class_exists($seed[0])) {
                throw new Exception('Seed class "' . $seed[0] . '" not found');
            }*/

            foreach ($seed as $k => $v) {
                if (is_int($k)) {
                    if (!isset($arguments[$k])) {
                        $arguments[$k] = $v;
                    }
                } elseif ($v !== null) {
                    if (!isset($injection[$k])) {
                        $injection[$k] = $v;
                    }
                }
            }
        }

        ksort($arguments, \SORT_NUMERIC);
        if ($obj === null) {
            $arguments += $injection;

            return $arguments;
        }

        unset($arguments[0]); // the first argument specifies a class name
        if (count($arguments) > 0) {
            throw (new Exception('Constructor arguments cannot be injected into existing object'))
                ->addMoreInfo('object', $obj)
                ->addMoreInfo('arguments', $arguments);
        }

        if (count($injection) > 0) {
            if (!TraitUtil::hasDiContainerTrait($obj)) {
                throw (new Exception('Property injection is possible only to objects that use Atk4\Core\DiContainerTrait trait'))
                    ->addMoreInfo('object', $obj)
                    ->addMoreInfo('injection', $injection);
            }

            if ($beforeObjKeys !== null) {
                $injectionActive = array_intersect_key($injection, $beforeObjKeys);
                $injection = array_diff_key($injection, $beforeObjKeys);

                $obj->setDefaults($injectionActive, false);
            }
            $obj->setDefaults($injection, true);
        }

        return $obj;
    }

    /**
     * @param class-string      $className
     * @param array<int, mixed> $ctorArgs
     */
    protected function _newObject(string $className, array $ctorArgs): object
    {
        return new $className(...$ctorArgs);
    }

    /**
     * @param array<mixed>|object $seed
     * @param array<mixed>        $defaults
     */
    protected function _factory($seed, array $defaults): object
    {
        if (!is_array($seed) && !is_object($seed)) { // @phpstan-ignore function.alreadyNarrowedType, booleanAnd.alwaysFalse
            throw new Exception('Use of non-array (' . gettype($seed) . ') seed is not supported');
        }

        array_unshift($defaults, null); // insert argument 0

        if (is_object($seed)) {
            $defaults = $this->_mergeSeeds([], $defaults);
            $defaults[0] = $seed;
            $seed = $defaults;
        } else {
            $seed = $this->_mergeSeeds($seed, $defaults);
        }
        unset($defaults);

        $arguments = array_filter($seed, 'is_int', \ARRAY_FILTER_USE_KEY); // with numeric keys
        $injection = array_diff_key($seed, $arguments); // with string keys
        $object = array_shift($arguments); // first numeric key argument is object

        if (!is_object($object)) {
            if (!is_string($object)) {
                throw (new Exception('Class name is not specified by the seed'))
                    ->addMoreInfo('seed', $seed);
            }

            $object = $this->_newObject($object, $arguments);
        }

        if (count($injection) > 0) {
            $this->_mergeSeeds($injection, $object);
        }

        return $object;
    }

    /**
     * Given two seeds (or more) will merge them, prioritizing the first argument.
     * If object is passed on either of arguments, then it will setDefaults() remaining
     * arguments, respecting their positioning.
     *
     * To learn more about mechanics of factory trait, see documentation
     *
     * @param array<mixed>|object|null ...$seeds
     *
     * @return ($seeds is object ? object : array<mixed>) if one seed is an object, that object is returned
     */
    final public static function mergeSeeds(...$seeds)
    {
        return self::getInstance()->_mergeSeeds(...$seeds);
    }

    /**
     * Given a Seed (see doc) as a first argument, will create object of a corresponding
     * class, call constructor with numerical arguments of a seed and inject key/value
     * arguments.
     *
     * Argument $defaults has the same effect as the seed, but will not contain the class.
     * Class is always determined by seed, except if you pass object into defaults.
     *
     * To learn more about mechanics of factory trait, see documentation
     *
     * @param array<mixed>|object $seed
     * @param array<mixed>        $defaults
     */
    final public static function factory($seed, $defaults = []): object
    {
        if ('func_num_args'() > 2) { // prevent bad usage
            throw new \Error('Too many method arguments');
        }

        if ($defaults === null) { // @phpstan-ignore identical.alwaysFalse (should be deprecated soon)
            $defaults = [];
        }

        return self::getInstance()->_factory($seed, $defaults);
    }
}
