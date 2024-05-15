<?php

declare(strict_types=1);

namespace Atk4\Core;

trait HookTrait
{
    /** @var array<string, array<int, array<int, array{\Closure, 1?: array<int, mixed>}>>> Configured hooks (callbacks). */
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
                    $dummyInstance = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
                    foreach ([$class, ...array_keys(class_parents($class))] as $scope) {
                        \Closure::bind(static function () use ($dummyInstance) {
                            foreach (array_keys(get_object_vars($dummyInstance)) as $k) {
                                unset($dummyInstance->{$k});
                            }
                        }, null, $scope)();
                    }

                    self::$_instances[$class] = $dummyInstance;
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
                        // example of legit use: https://github.com/atk4/audit/blob/eb9810e085/src/Controller.php#L85
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

        $index = $this->_hookIndexCounter++;
        $data = [$fx, $args];
        if ($priority < 0) {
            $this->hooks[$spot][$priority] = [$index => $data] + ($this->hooks[$spot][$priority] ?? []);
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
        if ($fxThis === null) {
            $fxLong = \Closure::bind(static function ($ignore, &...$args) use ($fx) {
                return $fx(...$args);
            }, null, $fxScopeClassRefl !== null ? $fxScopeClassRefl->getName() : null);
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
        if ($getFxThisFx !== null) {
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
                $fxThis = $getFxThisFx($target); // @phpstan-ignore argument.type
                if (!is_object($fxThis)) { // @phpstan-ignore function.alreadyNarrowedType
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
        return $this->onHook($spot, $this->_makeHookDynamicFx($getFxThisFx, $fx, false), $args, $priority);
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
        return $this->onHook($spot, $this->_makeHookDynamicFx($getFxThisFx, $fx, true), $args, $priority);
    }

    /**
     * Returns true if at least one callback is defined for this hook.
     *
     * @param ($priorityIsIndex is true ? int : int|null) $priority        filter specific priority, null for all
     * @param bool                                        $priorityIsIndex filter by index instead of priority
     */
    public function hookHasCallbacks(string $spot, ?int $priority = null, bool $priorityIsIndex = false): bool
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
     * Delete all hooks for specified spot, priority and index.
     *
     * @param ($priorityIsIndex is true ? int : int|null) $priority        filter specific priority, null for all
     * @param bool                                        $priorityIsIndex filter by index instead of priority
     *
     * @return static
     */
    public function removeHook(string $spot, ?int $priority = null, bool $priorityIsIndex = false)
    {
        if ($priority !== null) {
            if ($priorityIsIndex) {
                $index = $priority;
                unset($priority);

                foreach (array_keys($this->hooks[$spot] ?? []) as $priority) {
                    unset($this->hooks[$spot][$priority][$index]);

                    if ($this->hooks[$spot][$priority] === []) {
                        unset($this->hooks[$spot][$priority]);
                    }
                }
            } else {
                unset($this->hooks[$spot][$priority]);
            }

            if (($this->hooks[$spot] ?? null) === []) {
                unset($this->hooks[$spot]);
            }
        } else {
            unset($this->hooks[$spot]);
        }

        return $this;
    }

    /**
     * Execute all closures assigned to $spot.
     *
     * @param array<int, mixed> $args
     * @param mixed             $brokenBy
     *
     * @param-out HookBreaker|null $brokenBy
     *
     * @return array<int, mixed>|mixed Array of responses indexed by hook indexes or value specified to breakHook
     */
    public function hook(string $spot, array $args = [], &$brokenBy = null)
    {
        $brokenBy = null;

        $this->_rebindHooksIfCloned();

        $return = [];
        if (isset($this->hooks[$spot])) {
            krsort($this->hooks[$spot]); // lower priority is called sooner
            $hooksBackup = $this->hooks[$spot];
            $priorities = array_keys($hooksBackup);

            try {
                while (($priority = array_pop($priorities)) !== null) {
                    $hooks2Backup = $this->hooks[$spot][$priority];
                    $indexes = array_reverse(array_keys($hooks2Backup));

                    while (($index = array_pop($indexes)) !== null) {
                        [$hookFx, $hookArgs] = $this->hooks[$spot][$priority][$index];

                        $return[$index] = $hookFx($this, ...$args, ...$hookArgs);

                        if (!isset($this->hooks[$spot][$priority])) {
                            break;
                        } elseif ($hooks2Backup !== $this->hooks[$spot][$priority]) {
                            $hooks2Backup = $this->hooks[$spot][$priority];
                            $indexes = array_reverse(array_keys($hooks2Backup));
                            foreach ($indexes as $k => $i) {
                                if ($i <= $index) {
                                    unset($indexes[$k]);
                                }
                            }
                        }
                    }

                    if (!isset($this->hooks[$spot])) { // @phpstan-ignore isset.offset
                        break;
                    } elseif ($hooksBackup !== $this->hooks[$spot]) {
                        krsort($this->hooks[$spot]);
                        $hooksBackup = $this->hooks[$spot];
                        $priorities = array_keys($hooksBackup);
                        foreach ($priorities as $k => $p) {
                            if ($p <= $priority) {
                                unset($priorities[$k]);
                            }
                        }
                    }
                }
            } catch (HookBreaker $e) {
                $brokenBy = $e;

                return $e->getReturnValue();
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
