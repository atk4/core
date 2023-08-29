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
     * Magic method - tries to call dynamic method and throws exception if
     * this was not possible.
     *
     * @param string            $name Name of the method
     * @param array<int, mixed> $args Array of arguments to pass to this method
     *
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        $hookName = $this->buildMethodHookName($name);
        if (TraitUtil::hasHookTrait($this) && $this->hookHasCallbacks($hookName)) {
            $result = $this->hook($hookName, $args);

            return reset($result);
        }

        // match native PHP behaviour as much as possible
        // https://3v4l.org/eAv7t
        $class = static::class;
        do {
            if (method_exists($class, $name)) {
                $methodRefl = new \ReflectionMethod($class, $name);
                $visibility = $methodRefl->isPrivate()
                    ? 'private'
                    : ($methodRefl->isProtected() ? 'protected' : 'unknown');
                $fromScope = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null;

                throw new \Error('Call to ' . $visibility . ' method ' . $class . '::' . $name . '() from '
                    . ($fromScope ? 'scope ' . $fromScope : 'global scope'));
            }
        } while ($class = get_parent_class($class));
        $class = static::class;

        throw new \Error('Call to undefined method ' . $class . '::' . $name . '()');
    }

    private function buildMethodHookName(string $name): string
    {
        return '__atk4__dynamic_method__' . $name;
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
        if (!TraitUtil::hasHookTrait($this)) {
            throw new Exception('Object must use HookTrait for dynamic method support');
        }

        if ($this->hasMethod($name)) {
            throw (new Exception('Registering method twice'))
                ->addMoreInfo('name', $name);
        }

        $this->onHook($this->buildMethodHookName($name), $fx);

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
            || $this->hookHasCallbacks($this->buildMethodHookName($name));
    }

    /**
     * Remove dynamically registered method.
     *
     * @param string $name Name of the method
     *
     * @return $this
     */
    public function removeMethod(string $name)
    {
        $this->removeHook($this->buildMethodHookName($name));

        return $this;
    }
}
