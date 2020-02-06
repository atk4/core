<?php

namespace atk4\core\Concerns;

use atk4\core\Exception;

trait HasFactory
{
    use CanCheckTraits;
    
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

        $mergeObjectWithArray = function($seedObject, $seedArray, $passively = false) {
            // set defaults but don't override existing properties
            $arguments = array_filter($seedArray, 'is_numeric', ARRAY_FILTER_USE_KEY); // with numeric keys
            $properties = array_diff_key($seedArray, $arguments); // with string keys
            
            if (!$properties) return;
            
            if ($this->dispositionOf($seedObject, HasDIContainer::class)) {
                $seedObject->setDefaults($properties, $passively);
            } else {
                throw new Exception([
                        'factory() requested to passively inject some properties into existing object that does not use \atk4\core\DIContainerTrait',
                        'object'   => $seedObject,
                        'injection'=> $properties,
                ]);
            }
        };
        
        if (is_object($seed)) {
            if (is_array($seed2)) {
                $mergeObjectWithArray($seed, $seed2, true);
            }
            
            return $seed;
        }
        
        if (is_object($seed2)) {
            if (is_array($seed)) {
                $mergeObjectWithArray($seed2, $seed);
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
            if ($this->dispositionOf($object, HasDIContainer::class)) {
                $object->setDefaults($injection);
            } else {
                throw new Exception([
                    'factory() could not inject properties into new object. It does not use HasDIContainer trait',
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
     * Rules observed, in order:
     *  - If class starts with "." then prefixing is always done.
     *  - If class contains "\" prefixing is never done.
     *  - If class (with prefix) exists, do prefix.
     *  - don't prefix otherwise.
     *
     * Example: normalizeClassName('User', 'Model') == 'Model\User';
     * Example: normalizeClassName(Test\User::class, 'Model') == 'Test\User'; # or as per "use"
     * Example: normalizeClassName('Test/User', 'Model') == 'Model\Test\User';
     * Example: normalizeClassName('./User', 'Model') == 'Model\User';
     * Example: normalizeClassName('User', 'Model') == 'Model\User'; # if exists, 'User' otherwise
     *
     * # If used without namespace:
     * Example: normalizeClassName(User::class, 'Model') == 'Model\User'; # if exists, 'User' otherwise
     *
     * @param string $name   Name of class
     * @param string $prefix Optional prefix for class name
     *
     * @return string Full, normalized class name
     */
    public function normalizeClassName($name, $prefix = null)
    {
        // If App has "normalizeClassName" (obsolete now), use it instead
        if (
            $this->disposition(HasAppScope::class)
            && method_exists($this->app, 'normalizeClassNameApp')
        ) {
            $result = $this->app->normalizeClassNameApp($name, $prefix);

            if (!is_null($result)) {
                return $result;
            }
        }

        // Rule 1: if starts with "." always prefix
        if ($name && $name[0] == '.' && $prefix) {
            $name = $prefix.'\\'.substr($name, 1);
            $name = str_replace('/', '\\', $name);

            return $name;
        }

        // Rule 2: if "\" is present, don't prefix
        if (strpos($name, '\\') !== false) {
            $name = str_replace('/', '\\', $name);

            return $name;
        }

        if ($name && $name[0] !== '/' && $prefix) {
            $name = $prefix.'\\'.$name;
        }

        $name = str_replace('/', '\\', $name);

        return $name;
    }
}
