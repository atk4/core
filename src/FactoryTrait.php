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
     * Given a Seed (see doc) as a first argument, will create object of a corresponding
     * class, call constructor with numerical arguments of a seed and inject key/value
     * arguments.
     *
     * Argument $defaults has the same effect as the seed, but allows you to separate
     * out initialization for convenience, e.g. factory(['Button', 'label']); is same as
     * factory('Button', ['label']). Second argument may not affect the class, so it's
     * safer.
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
            throw new Exception(['Incorrect seed given, try [\'ClassName\']', 'seed'=>$seed]);
        }

        if (!is_array($seed)) {
            $seed = [$seed];
        }

        foreach ($seed as $key=>$value) {
            $defaults[$key] = $value;
        }

        $arguments = array_filter($defaults, 'is_numeric', ARRAY_FILTER_USE_KEY);

        $object = array_shift($arguments);

        $injection = array_filter(
            $defaults,
            function ($o) {
                return !is_numeric($o);
            },
            ARRAY_FILTER_USE_KEY
        );

        // If object is passed to us, we can ignore arguments, but we need to inject defaults
        if (is_object($object)) {
            if ($injection) {
                if (isset($object->_DIContainerTrait)) {
                    $object->setDefaults($injection);
                } else {
                    throw new Exception([
                        'factory() requested to inject some properties into existing object that does not use \atk4\core\DIContainerTrait',
                        'object'   => $object,
                        'injection'=> $injection,
                    ]);
                }
            }

            return $object;
        }

        $class = $this->normalizeClassName($object, $prefix);

        if (!$class) {
            throw new Exception([
                'Class name was not specified by the seed',
                'seed'=> $seed,
            ]);
        }

        $object = new $class(...$arguments);

        if ($injection) {
            if (isset($object->_DIContainerTrait)) {
                $object->setDefaults($injection);
            } else {
                throw new Exception([
                    'factory() could not inject properties into new object. It does not use \atk4\core\DIContainerTrait',
                    'object'   => $object,
                    'class'    => $class,
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
