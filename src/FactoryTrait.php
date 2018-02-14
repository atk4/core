<?php

namespace atk4\core;

trait FactoryTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_factoryTrait = true;

    /**
     * Given two seeds (or more) will merge them, prioritizing the first argument.
     * If object is passed on either of arguments, then it will setDefaults() remaining
     * arguments, respecting their positioning.
     *
     * See full documentation.
     *
     * @param array|object|mixed $seed
     * @param array|object|mixed $seed2
     * @param array              $more_seeds
     *
     * @return object|array if at least one seed is an object, will return object
     */
    public function mergeSeeds($seed, $seed2, ...$more_seeds)
    {
        // recursively merge extra seeds
        if ($more_seeds) {
            $seed2 = $this->mergeSeeds($seed2, ...$more_seeds);
        }

        if (is_object($seed)) {
            if (is_array($seed2)) {
                // set defaults but don't override existing properties
                $arguments = array_filter($seed2, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
                $injection = array_diff_key($seed2, $arguments); // with string keys
                if ($injection) {
                    if (isset($seed->_DIContainerTrait)) {
                        $seed->setDefaults($injection, true);
                    } else {
                        throw new Exception([
                            'factory() requested to passively inject some properties into existing object that does not use \atk4\core\DIContainerTrait',
                            'object'   => $seed,
                            'injection'=> $injection,
                        ]);
                    }
                }
            }

            return $seed;
        }

        if (is_object($seed2)) {
            // seed is not object, and setDefaults will complain if it's not array
            if (is_array($seed)) {
                $arguments = array_filter($seed, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
                $injection = array_diff_key($seed, $arguments); // with string keys
                if ($injection) {
                    if (isset($seed2->_DIContainerTrait)) {
                        $seed2->setDefaults($injection);
                    } else {
                        throw new Exception([
                            'factory() requested to inject some properties into existing object that does not use \atk4\core\DIContainerTrait',
                            'object'   => $seed2,
                            'injection'=> $seed,
                        ]);
                    }
                }
            }

            return $seed2;
        }

        if (!is_array($seed)) {
            $seed = [$seed];
        }

        if (!is_array($seed2)) {
            $seed2 = [$seed2];
        }

        // overwrite seed2 with seed
        foreach ($seed as $key=>$value) {
            if ($value !== null) {
                $seed2[$key] = $value;
            } elseif (is_numeric($key) && !isset($seed2[$key])) {
                $seed2[$key] = $value;
            }
        }

        return $seed2;
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
     * @param mixed  $seed
     * @param array  $defaults
     * @param string $prefix   Optional prefix for class name
     *
     * @return object
     */
    public function factory($seed, $defaults = [], $prefix = null)
    {
        if ($defaults === null) {
            $defaults = [];
        }

        if (!$seed) {
            $seed = [];
        }

        if (!is_array($seed)) {
            $seed = [$seed];
        }

        if (is_array($defaults)) {
            array_unshift($defaults, null); // insert argument 0
        } elseif (!is_object($defaults)) {
            $defaults = [null, $defaults];
        }
        $seed = $this->mergeSeeds($seed, $defaults);

        if (is_object($seed)) {
            // setDefaults already called
            return $seed;
        }

        $object = array_shift($seed); // first numeric key argument is object

        $arguments = array_filter($seed, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
        $injection = array_diff_key($seed, $arguments); // with string keys

        if (is_string($object)) {
            $class = $this->normalizeClassName($object, $prefix);

            if (!$class) {
                throw new Exception([
                    'Class name was not specified by the seed',
                    'seed'=> $seed,
                ]);
            }

            $object = new $class(...$arguments);
        }

        if ($injection) {
            if (isset($object->_DIContainerTrait)) {
                $object->setDefaults($injection);
            } else {
                throw new Exception([
                    'factory() could not inject properties into new object. It does not use \atk4\core\DIContainerTrait',
                    'object'   => $object,
                    'seed'     => $seed,
                    'injection'=> $injection,
                ]);
            }
        }

        return $object;
    }

    /**
     * First normalize class name, then add specified prefix to
     * class name. Finally if $app is defined, and has method
     * `normalizeClassNameApp` it will also get a chance to
     * add prefix.
     *
     * Rule observed: If first character of class, or prefix is
     * '/' or '\' then no more prefixing is done. Also after all the
     * prefixing took place, the slashes '/' will be replaced
     * with '\'.
     *
     * Example: normalizeClassName('User', 'Model') == 'Model\User';
     *
     * @param mixed  $name   Name of class or object
     * @param string $prefix Optional prefix for class name
     *
     * @return string|object Full, normalized class name or received object
     */
    public function normalizeClassName($name, $prefix = null)
    {
        if (!$name) {
            if (
                isset($this->_appScopeTrait, $this->app)
                && method_exists($this->app, 'normalizeClassNameApp')
            ) {
                $name = $this->app->normalizeClassNameApp($name);
            }

            return $name;
        }

        // Add prefix only if name doesn't start with / and name doesn't contain \\
        if ($name[0] != '/' && $name[0] != '\\' && $prefix) {
            $name = $prefix.'\\'.$name;
        }

        if (
            $name[0] != '/'
            && $name[0] != '\\'
            && isset($this->_appScopeTrait, $this->app)
            && method_exists($this->app, 'normalizeClassNameApp')
        ) {
            $name = $this->app->normalizeClassNameApp($name);
        }

        $name = str_replace('/', '\\', $name);

        return $name;
    }
}
