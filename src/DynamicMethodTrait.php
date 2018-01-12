<?php

namespace atk4\core;

/**
 * This trait makes it possible for you to add dynamic methods
 * into your object.
 */
trait DynamicMethodTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_dynamicMethodTrait = true;

    /**
     * Magic method - tries to call dynamic method and throws exception if
     * this was not possible.
     *
     * @param string $name      Name of the method
     * @param array  $arguments Array of arguments to pass to this method
     */
    public function __call($method, $arguments)
    {
        if ($ret = $this->tryCall($method, $arguments)) {
            return $ret[0];
        }

        throw new Exception([
            'Method is not defined for this object',
                'class'     => get_class($this),
                'method'    => $method,
                'arguments' => $arguments,
            ]);
    }

    /**
     * Tries to call dynamic method.
     *
     * @param string $name      Name of the method
     * @param array  $arguments Array of arguments to pass to this method
     *
     * @return mixed|null
     */
    public function tryCall($method, $arguments)
    {
        if (isset($this->_hookTrait) && $ret = $this->hook('method-'.$method, $arguments)) {
            return $ret;
        }

        if (isset($this->_appScopeTrait) && isset($this->app->_hookTrait)) {
            array_unshift($arguments, $this);
            if ($ret = $this->app->hook('global-method-'.$method, $arguments)) {
                return $ret;
            }
        }
    }

    /**
     * Add new method for this object.
     *
     * @param string|array $name     Name of new method of $this object
     * @param callable     $callable Callback
     *
     * @return $this
     */
    public function addMethod($name, $callable)
    {
        // HookTrait is mandatory
        if (!isset($this->_hookTrait)) {
            throw new Exception(['Object must use hookTrait for Dynamic Methods to work']);
        }

        if (is_string($name) && strpos($name, ',') !== false) {
            $name = array_map('trim', explode(',', $name));
        }

        if (is_array($name)) {
            foreach ($name as $h) {
                $this->addMethod($h, $callable);
            }

            return $this;
        }

        if (is_object($callable) && !is_callable($callable)) {
            $callable = [$callable, $name];
        }

        if ($this->hasMethod($name)) {
            throw new Exception(['Registering method twice', 'name' => $name]);
        }

        $this->addHook('method-'.$name, $callable);

        return $this;
    }

    /**
     * Return if this object has specified method (either native or dynamic).
     *
     * @param string $name Name of the method
     *
     * @return bool
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name)
            || (isset($this->_hookTrait) && $this->hookHasCallbacks('method-'.$name))
            || $this->hasGlobalMethod($name);
    }

    /**
     * Remove dynamically registered method.
     *
     * @param string $name Name of the method
     *
     * @return $this
     */
    public function removeMethod($name)
    {
        if (isset($this->_hookTrait)) {
            $this->removeHook('method-'.$name);
        }

        return $this;
    }

    /**
     * Agile Toolkit objects allow method injection. This is quite similar
     * to technique used in JavaScript:.
     *
     *     obj.test = function() { .. }
     *
     * All non-existent method calls on all Agile Toolkit objects will be
     * tried against local table of registered methods and then against
     * global registered methods.
     *
     * addGlobalMethod allows you to register a globally-recognized method for
     * all Agile Toolkit objects. PHP is not particularly fast about executing
     * methods like that, but this technique can be used for adding
     * backward-compatibility or debugging, etc.
     *
     * @see self::hasMethod()
     * @see self::__call()
     *
     * @param string   $name     Name of the method
     * @param callable $callable Calls your function($object, $arg1, $arg2)
     */
    public function addGlobalMethod($name, $callable)
    {
        // AppScopeTrait and HookTrait for app are mandatory
        if (!isset($this->_appScopeTrait) || !isset($this->app->_hookTrait)) {
            throw new Exception(['You need AppScopeTrait and HookTrait traits, see docs']);
        }

        if ($this->hasGlobalMethod($name)) {
            throw new Exception(['Registering global method twice', 'name' => $name]);
        }
        $this->app->addHook('global-method-'.$name, $callable);
    }

    /**
     * Return true if such global method exists.
     *
     * @param string $name Name of the method
     *
     * @return bool
     */
    public function hasGlobalMethod($name)
    {
        return
            isset($this->_appScopeTrait) &&
            isset($this->app->_hookTrait) &&
            $this->app->hookHasCallbacks('global-method-'.$name);
    }

    /**
     * Remove dynamically registered global method.
     *
     * @param string $name Name of the method
     *
     * @return $this
     */
    public function removeGlobalMethod($name)
    {
        if (isset($this->_appScopeTrait) && isset($this->app->_hookTrait)) {
            $this->app->removeHook('global-method-'.$name);
        }

        return $this;
    }
}
