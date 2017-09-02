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
     * @param mixed $seed
     * @param array $defaults
     *
     * @return object
     */
    public function factory($seed, $defaults = [])
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

        $arguments1 = array_filter($seed, 'is_numeric', ARRAY_FILTER_USE_KEY);
        $arguments2 = array_filter($defaults, 'is_numeric', ARRAY_FILTER_USE_KEY);

        $object = array_shift($arguments1);
        $arguments = array_merge($arguments1, $arguments2);

        $injection = array_filter(
            array_merge($defaults, $seed),
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

        $class = $this->normalizeClassName($object);

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
     * class name if it's passed and not already added.
     * Class name can contain namespace.
     *
     * If object is passed as $name parameter, then same object is returned.
     *
     * Example: normalizeClassName('User','Model') == 'Model_User';
     *
     * @param mixed  $name   Name of class or object
     * @param string $prefix Optional prefix for class name
     *
     * @return string|object Full, normalized class name or received object
     */
    public function normalizeClassName($name, $prefix = null)
    {
        if (is_array($name) && isset($name[0])) {
            $name[0] = $this->normalizeClassName($name[0]);

            return $name;
        }

        if (
            is_string($name)
            && isset($this->_appScopeTrait, $this->app)
            && method_exists($this->app, 'normalizeClassNameApp')
        ) {
            $name = $this->app->normalizeClassNameApp($name, $prefix);
        }

        if (!is_string($name)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);
        if ($prefix !== null) {
            $class = ltrim(strrchr($name, '\\'), '\\') ?: $name;
            $prefix = ucfirst($prefix);
            if (strpos($class, $prefix) !== 0) {
                $name = preg_replace('|^(.*\\\)?(.*)$|', '\1'.$prefix.'_\2', $name);
            }
        }

        return $name;
    }
}
