<?php

declare(strict_types=1);

namespace atk4\core;

class Factory
{
    /** @var Factory */
    private static $_instance;

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

    protected function _mergeSeeds($seed, $seed2, ...$more_seeds)
    {
        // recursively merge extra seeds
        if (count($more_seeds) > 0) {
            $seed2 = $this->_mergeSeeds($seed2, ...$more_seeds);
        }

        if (is_object($seed) || is_object($seed2)) {
            if (is_object($seed)) {
                $passively = true; // set defaults but don't override existing properties
            } else {
                $passively = false;
                [$seed, $seed2] = [$seed2, $seed]; // swap seeds
            }

            if (is_array($seed2)) {
                $arguments = array_filter($seed2, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
                $injection = array_diff_key($seed2, $arguments); // with string keys
                unset($seed2);
                unset($arguments[0]); // the first argument specifies a class name

                if (count($arguments) > 0) {
                    throw (new Exception('Constructor arguments can not be injected into existing object'))
                        ->addMoreInfo('object', $seed)
                        ->addMoreInfo('arguments', $arguments);
                }

                if (count($injection) > 0) {
                    if (isset($seed->_DIContainerTrait)) {
                        $seed->setDefaults($injection, $passively);
                    } else {
                        throw (new Exception('Property injection is possible only to objects that use \atk4\core\DIContainerTrait trait'))
                            ->addMoreInfo('object', $seed)
                            ->addMoreInfo('injection', $injection)
                            ->addMoreInfo('passively', $passively);
                    }
                }
            }

            return $seed;
        }

        if (!is_array($seed)) {
            $seed = [$seed];
        }

        if (!is_array($seed2)) {
            $seed2 = [$seed2];
        }

        // merge seeds but prefer seed over seed2
        // move numerical keys to the beginning and sort them
        $res = [];
        $res2 = [];
        foreach ($seed as $k => $v) {
            if (is_numeric($k)) {
                $res[$k] = $v;
            } elseif ($v !== null) {
                $res2[$k] = $v;
            }
        }
        foreach ($seed2 as $k => $v) {
            if (is_numeric($k)) {
                if (!isset($res[$k])) {
                    $res[$k] = $v;
                }
            } elseif ($v !== null) {
                if (!isset($res2[$k])) {
                    $res2[$k] = $v;
                }
            }
        }
        ksort($res, SORT_NUMERIC);
        $res = $res + $res2;

        return $res;
    }

    protected function createNewObject(string $className, array $ctorArgs): object
    {
        return new $className(...$ctorArgs);
    }

    protected function _factory($seed, $defaults = []): object
    {
        if ($defaults === null) { // should be deprecated soon
            $defaults = [];
        }

        if ($seed === null) { // should be deprecated soon
            $seed = [];
        } elseif (!is_array($seed)) {
            $seed = [$seed];
        }

        if (is_array($defaults)) {
            array_unshift($defaults, null); // insert argument 0
        } elseif (!is_object($defaults)) {
            $defaults = [null, $defaults];
        }
        $seed = $this->_mergeSeeds($seed, $defaults);

        if (is_object($seed)) {
            // setDefaults() already called in _mergeSeeds()

            return $seed;
        }

        $arguments = array_filter($seed, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
        $injection = array_diff_key($seed, $arguments); // with string keys
        $object = array_shift($arguments); // first numeric key argument is object

        if (!is_object($object)) {
            if (!is_string($object)) {
                throw (new Exception('Class name is not specified by the seed'))
                    ->addMoreInfo('seed', $seed);
            }

            $object = $this->createNewObject($object, $arguments);
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
     * @return object|array if at least one seed is an object, will return object
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
     * @param array $defaults
     */
    final public static function factory($seed, $defaults = []): object
    {
        if (func_num_args() > 2) { // prevent bad usage
            throw new \Error('Too many method arguments');
        }

        return self::getInstance()->_factory($seed, $defaults);
    }
}
