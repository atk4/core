<?php

declare(strict_types=1);

namespace Atk4\Core;

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
     * @param string $name Name of the method
     * @param array  $args Array of arguments to pass to this method
     */
    public function __call(string $name, $args)
    {
        if ($ret = $this->tryCall($name, $args)) {
            return reset($ret);
        }

        throw (new Exception('Method ' . $name . ' is not defined for this object'))
            ->addMoreInfo('class', static::class)
            ->addMoreInfo('method', $name)
            ->addMoreInfo('args', $args);
    }

    private function buildMethodHookName(string $name, bool $isGlobal): string
    {
        return '__atk__method__' . ($isGlobal ? 'g' : 'l') . '__' . $name;
    }

    /**
     * Tries to call dynamic method.
     *
     * @param string $name Name of the method
     * @param array  $args Array of arguments to pass to this method
     *
     * @return mixed|null
     */
    public function tryCall($name, $args)
    {
        if (isset($this->_hookTrait) && $ret = $this->hook($this->buildMethodHookName($name, false), $args)) {
            return $ret;
        }

        if (isset($this->_appScopeTrait) && isset($this->app->_hookTrait)) {
            array_unshift($args, $this);
            if ($ret = $this->app->hook($this->buildMethodHookName($name, true), $args)) {
                return $ret;
            }
        }
    }

    /**
     * Add new method for this object.
     *
     * @param string $name Name of new method of $this object
     *
     * @return $this
     */
    public function addMethod(string $name, \Closure $fx)
    {
        // HookTrait is mandatory
        if (!isset($this->_hookTrait)) {
            throw new Exception('Object must use hookTrait for Dynamic Methods to work');
        }

        if ($this->hasMethod($name)) {
            throw (new Exception('Registering method twice'))
                ->addMoreInfo('name', $name);
        }

        $this->onHook($this->buildMethodHookName($name, false), $fx);

        return $this;
    }

    /**
     * Return if this object has specified method (either native or dynamic).
     *
     * @param string $name Name of the method
     */
    public function hasMethod(string $name): bool
    {
        return method_exists($this, $name)
            || (isset($this->_hookTrait) && $this->hookHasCallbacks($this->buildMethodHookName($name, false)))
            || $this->hasGlobalMethod($name);
    }

    /**
     * Remove dynamically registered method.
     *
     * @param string $name Name of the method
     */
    public function removeMethod(string $name)
    {
        if (isset($this->_hookTrait)) {
            $this->removeHook($this->buildMethodHookName($name, false));
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
     * @param string $name Name of the method
     */
    public function addGlobalMethod(string $name, \Closure $fx): void
    {
        // AppScopeTrait and HookTrait for app are mandatory
        if (!isset($this->_appScopeTrait) || !isset($this->app->_hookTrait)) {
            throw new Exception('You need AppScopeTrait and HookTrait traits, see docs');
        }

        if ($this->hasGlobalMethod($name)) {
            throw (new Exception('Registering global method twice'))
                ->addMoreInfo('name', $name);
        }

        $this->app->onHook($this->buildMethodHookName($name, true), $fx);
    }

    /**
     * Return true if such global method exists.
     *
     * @param string $name Name of the method
     */
    public function hasGlobalMethod(string $name): bool
    {
        return
            isset($this->_appScopeTrait) &&
            isset($this->app->_hookTrait) &&
            $this->app->hookHasCallbacks($this->buildMethodHookName($name, true));
    }

    /**
     * Remove dynamically registered global method.
     *
     * @param string $name Name of the method
     */
    public function removeGlobalMethod(string $name): void
    {
        if (isset($this->_appScopeTrait) && isset($this->app->_hookTrait)) {
            $this->app->removeHook($this->buildMethodHookName($name, true));
        }
    }
}
