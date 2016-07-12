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
     * Creates and returns new object.
     * If object is passed as $object parameter, then same object is returned.
     *
     * @param object|string $object
     * @param array $defaults
     *
     * @return object
     */
    public function factory($object, $defaults = [])
    {
        if (is_object($object)) {
            return $object;
        }
        if (!is_string($object)) {
            throw new Exception([
                'Factory needs object or string',
                'object'   => $object,
                'defaults' => $defaults,
            ]);
        }

        $object = $this->normalizeClassName($object);

        return new $object($defaults);
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
     * @param string|object $name   Name of class or object
     * @param string        $prefix Optional prefix for class name
     *
     * @return string|object Full, normalized class name or received object
     */
    public function normalizeClassName($name, $prefix = null)
    {
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
