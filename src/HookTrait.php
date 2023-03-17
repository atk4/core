<?php

declare(strict_types=1);

namespace Atk4\Core;

trait HookTrait
{
    /**
     * Contains information about configured hooks (callbacks).
     *
     * @var array<string, array<int, array<int, array{\Closure, 1?: array<int, mixed>}>>>
     */
    protected array $hooks = [];

    /** Next hook index counter. */
    private int $_hookIndexCounter = 0;

    /** @var \WeakReference<static>|null */
    private ?\WeakReference $_hookOrigThis = null;

    /**
     * Optimize GC. When a Closure is guaranteed to be rebound before invoke, it can be rebound
     * to (deduplicated) fake instance before safely.
     */
    private function _rebindHookFxToFakeInstance(\Closure $fx): \Closure
    {
        $fxThis = (new \ReflectionFunction($fx))->getClosureThis();

        $instanceWithoutConstructorCache = new class() {
            /** @var array<class-string, object> */
            private static array $_instances = [];

            /**
             * @param class-string $class
             */
            public function getInstance(string $class): object
            {
                if (!isset(self::$_instances[$class])) {
                    self::$_instances[$class] = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
                }

                return self::$_instances[$class];
            }
        };
        $fakeThis = $instanceWithoutConstructorCache->getInstance(get_class($fxThis));

        return \Closure::bind($fx, $fakeThis);
    }

    /**
     * When hook Closure is bound to $this, rebinding all hooks after clone can be slow, optimize clone
     * by unbinding $this in favor of rebinding $this when hook is invoked.
     */
    private function _unbindHookFxIfBoundToThis(\Closure $fx, bool $isShort): \Closure
    {
        $fxThis = (new \ReflectionFunction($fx))->getClosureThis();
        if ($fxThis !== $this) {
            return $fx;
        }

        $fx = $this->_rebindHookFxToFakeInstance($fx);

        return $this->_makeHookDynamicFx(null, $fx, $isShort);
    }

    private function _rebindHooksIfCloned(): void
    {
        if ($this->_hookOrigThis !== null) {
            $hookOrigThis = $this->_hookOrigThis->get();
            if ($hookOrigThis === $this) {
                return;
            }

            foreach ($this->hooks as $spot => $hooksByPriority) {
                foreach ($hooksByPriority as $priority => $hooksByIndex) {
                    foreach ($hooksByIndex as $index => $hookData) {
                        $fxRefl = new \ReflectionFunction($hookData[0]);
                        $fxThis = $fxRefl->getClosureThis();
                        if ($fxThis === null) {
                            continue;
                        }

                        // TODO we throw only if the class name is the same, otherwise the check is too strict
                        // and on a bad side - we should not throw when an object with a hook is cloned,
                        // but instead we should throw once the closure this object is cloned
                        // example of legit use: https://github.com/atk4/audit/blob/eb9810e085a40caedb435044d7318f4d8dd93e11/src/Controller.php#L85
                        if (get_class($fxThis) === static::class || preg_match('~^Atk4\\\\(?:Core|Data)~', get_class($fxThis))) {
                            throw (new Exception('Object cannot be cloned with hook bound to a different object than this'))
                                ->addMoreInfo('closure_file', $fxRefl->getFileName())
                                ->addMoreInfo('closure_start_line', $fxRefl->getStartLine());
                        }
                    }
                }
            }
        }

        $this->_hookOrigThis = \WeakReference::create($this);
    }

    /**
     * Add another callback to be executed during hook($spot);.
     *
     * Lower priority is called sooner.
     *
     * If priority is negative, then hook is prepended (executed first for the same priority).
     *
     * @param array<int, mixed> $args
     *
     * @return int index under which the hook was added
     */
    public function onHook(string $spot, \Closure $fx, array $args = [], int $priority = 5): int
    {
        $this->_rebindHooksIfCloned();

        $fx = $this->_unbindHookFxIfBoundToThis($fx, false);

        if (!isset($this->hooks[$spot][$priority])) {
            $this->hooks[$spot][$priority] = [];
        }

        $index = $this->_hookIndexCounter++;
        $data = [$fx, $args];
        if ($priority < 0) {
            $this->hooks[$spot][$priority] = [$index => $data] + $this->hooks[$spot][$priority];
        } else {
            $this->hooks[$spot][$priority][$index] = $data;
        }

        return $index;
    }

    /**
     * Same as onHook() except no $this is passed to the callback as the 1st argument.
     *
     * @param array<int, mixed> $args
     *
     * @return int index under which the hook was added
     */
    public function onHookShort(string $spot, \Closure $fx, array $args = [], int $priority = 5): int
    {
        // create long callback and bind it to the same scope class and object
        $fxRefl = new \ReflectionFunction($fx);
        $fxScopeClassRefl = $fxRefl->getClosureScopeClass();
        $fxThis = $fxRefl->getClosureThis();
        if ($fxScopeClassRefl === null) {
            $fxLong = static function ($ignore, &...$args) use ($fx) {
                return $fx(...$args);
            };
        } elseif ($fxThis === null) {
            $fxLong = \Closure::bind(static function ($ignore, &...$args) use ($fx) {
                return $fx(...$args);
            }, null, $fxScopeClassRefl->getName());
        } else {
            $fxLong = $this->_unbindHookFxIfBoundToThis($fx, true);
            if ($fxLong === $fx) {
                $fx = $this->_rebindHookFxToFakeInstance($fx);

                $fxLong = \Closure::bind(function ($ignore, &...$args) use ($fx) {
                    return \Closure::bind($fx, $this)(...$args);
                }, $fxThis, $fxScopeClassRefl->getName());
            }
        }

        return $this->onHook($spot, $fxLong, $args, $priority);
    }

    /**
     * @param \Closure($this): object $getFxThisFx
     */
    private function _makeHookDynamicFx(?\Closure $getFxThisFx, \Closure $fx, bool $isShort): \Closure
    {
        if ($getFxThisFx === null) {
            $getFxThisFxThis = null;
        } else {
            $getFxThisFxThis = (new \ReflectionFunction($getFxThisFx))->getClosureThis();
            if ($getFxThisFxThis !== null) {
                throw new \TypeError('New $this getter must be static');
            }
        }

        $fx = $this->_rebindHookFxToFakeInstance($fx);

        return static function (self $target, &...$args) use ($getFxThisFx, $fx, $isShort) {
            if ($getFxThisFx === null) {
                $fxThis = $target;
            } else {
                $fxThis = $getFxThisFx($target); // @phpstan-ignore-line
                if (!is_object($fxThis)) { // @phpstan-ignore-line
                    throw new \TypeError('New $this must be an object');
                }
            }

            return $isShort
                ? \Closure::bind($fx, $fxThis)(...$args)
                : \Closure::bind($fx, $fxThis)($target, ...$args);
        };
    }

    /**
     * Same as onHook() except $this of the callback is dynamically rebound before invoke.
     *
     * @param \Closure($this): object $getFxThisFx
     * @param array<int, mixed>       $args
     *
     * @return int index under which the hook was added
     */
    public function onHookDynamic(string $spot, \Closure $getFxThisFx, \Closure $fx, array $args = [], int $priority = 5): int
    {
        return $this->onHook($spot, $this->_makeHookDynamicFx($getFxThisFx, $fx, false), $args, $priority); // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/9009
    }

    /**
     * Same as onHookDynamic() except no $this is passed to the callback as the 1st argument.
     *
     * @param \Closure($this): object $getFxThisFx
     * @param array<int, mixed>       $args
     *
     * @return int index under which the hook was added
     */
    public function onHookDynamicShort(string $spot, \Closure $getFxThisFx, \Closure $fx, array $args = [], int $priority = 5): int
    {
        return $this->onHook($spot, $this->_makeHookDynamicFx($getFxThisFx, $fx, true), $args, $priority); // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/9009
    }

    /**
     * Delete all hooks for specified spot, priority and index.
     *
     * @param int|null $priority        filter specific priority, null for all
     * @param bool     $priorityIsIndex filter by index instead of priority
     *
     * @return static
     */
    public function removeHook(string $spot, int $priority = null, bool $priorityIsIndex = false)
    {
        if ($priority !== null) {
            if ($priorityIsIndex) {
                $index = $priority;
                unset($priority);

                foreach (array_keys($this->hooks[$spot]) as $priority) {
                    unset($this->hooks[$spot][$priority][$index]);
                }
            } else {
                unset($this->hooks[$spot][$priority]);
            }
        } else {
            unset($this->hooks[$spot]);
        }

        return $this;
    }

    /**
     * Returns true if at least one callback is defined for this hook.
     *
     * @param int|null $priority        filter specific priority, null for all
     * @param bool     $priorityIsIndex filter by index instead of priority
     */
    public function hookHasCallbacks(string $spot, int $priority = null, bool $priorityIsIndex = false): bool
    {
        if (!isset($this->hooks[$spot])) {
            return false;
        } elseif ($priority === null) {
            return true;
        }

        if ($priorityIsIndex) {
            $index = $priority;
            unset($priority);

            foreach (array_keys($this->hooks[$spot]) as $priority) {
                if (isset($this->hooks[$spot][$priority][$index])) {
                    return true;
                }
            }

            return false;
        }

        return isset($this->hooks[$spot][$priority]);
    }

    /**
     * Execute all closures assigned to $spot.
     *
     * @param array<int, mixed> $args
     *
     * @return array<int, mixed>|mixed Array of responses indexed by hook indexes or value specified to breakHook
     */
    public function hook(string $spot, array $args = [], HookBreaker &$brokenBy = null)
    {
        $brokenBy = null;
        $this->_rebindHooksIfCloned();

        $return = [];
        if (isset($this->hooks[$spot])) {
            krsort($this->hooks[$spot]); // lower priority is called sooner
            $hooksBackup = $this->hooks[$spot];
            try {
                while ($hooks = array_pop($this->hooks[$spot])) {
                    foreach ($hooks as $index => [$hookFx, $hookArgs]) {
                        $return[$index] = $hookFx($this, ...$args, ...$hookArgs);
                    }
                }
            } catch (HookBreaker $e) {
                $brokenBy = $e;

                return $e->getReturnValue();
            } finally {
                $this->hooks[$spot] = $hooksBackup;
            }
        }

        return $return;
    }

    /**
     * When called from inside a hook closure, it will stop execution of other
     * closures on the same hook. The passed argument will be returned by the
     * hook method.
     *
     * @param mixed $return What would hook() return?
     */
    public function breakHook($return): void
    {
        throw new HookBreaker($return);
    }
}
