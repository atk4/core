<?php
namespace atk4\core;

trait FactoryTrait {

    public $_factoryTrait = true;

    /**
     * Determine class name, call constructor
     */
    function factory($object, $defaults = []) {
        if (is_object($object)) {
            return $object;
        }
        if (!is_string($object)) {
            throw new Exception([
                'Factory needs object or string',
                'arg'=>$object,
                'defaults'=>$defaults,
            ]);
        }
        return new $object($defaults);
    }

    /**
     * First normalize class name, then add specified prefix to
     * class name if it's passed and not already added.
     * Class name can have namespaces and they are treated prefectly.
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
