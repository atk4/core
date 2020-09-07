<?php

declare(strict_types=1);

namespace atk4\core;

trait HookTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_hookTrait = true;

    /**
     * Contains information about configured hooks (callbacks).
     *
     * @var array
     */
    protected $hooks = [];

    /**
     * Next hook index counter.
     *
     * @var int
     */
    private $_hookIndexCounter = 0;

    /**
     * @var static
     */
    private $_hookOrigThis;

    private function _rebindHooksIfCloned(): void
    {
        if ($this->_hookOrigThis === $this) {
            return;
        } elseif ($this->_hookOrigThis === null) {
            $this->_hookOrigThis = $this;

            return;
        }

        foreach ($this->hooks as &$hooksByPriority) {
            foreach ($hooksByPriority as &$hooksByIndex) {
                foreach ($hooksByIndex as &$hookData) {
                    $fxThis = (new \ReflectionFunction($hookData[0]))->getClosureThis();
                    if ($fxThis === null) {
                        continue;
                    }

                    if ($fxThis !== $this->_hookOrigThis) {
                        throw new Exception('Object can not be cloned with hook bound to a different object than this');
                    }

                    $hookData[0] = \Closure::bind($hookData[0], $this);
                }
            }
        }
        unset($hooksByPriority, $hooksByIndex, $hookData);

        $this->_hookOrigThis = $this;
    }

    /**
     * Add another callback to be executed during hook($hook_spot);.
     *
     * Lower priority is called sooner. If priority is negative,
     * then hooks will be executed in reverse order.
     *
     * @return int Index under which the hook was added
     */
    public function onHook(string $spot, \Closure $fx, array $args = [], int $priority = 5): int
    {
        $this->_rebindHooksIfCloned();

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
     * @return int Index under which the hook was added
     */
    public function onHookShort(string $spot, \Closure $fx, array $args = [], int $priority = 5): int
    {
        // create long callback and bind it to the same scope class and object
        $fxRefl = new \ReflectionFunction($fx);
        $fxScopeClassRefl = $fxRefl->getClosureScopeClass();
        $fxThis = $fxRefl->getClosureThis();
        if ($fxScopeClassRefl === null) {
            $fxLong = static function ($ignore, ...$args) use ($fx) {
                return $fx(...$args);
            };
        } elseif ($fxThis === null) {
            $fxLong = \Closure::bind(function ($ignore, ...$args) use ($fx) {
                return $fx(...$args);
            }, null, $fxScopeClassRefl->getName());
        } else {
            $fxLong = \Closure::bind(function ($ignore, ...$args) use ($fx) {
                return \Closure::bind($fx, $this)(...$args);
            }, $fxThis, $fxScopeClassRefl->getName());
        }

        return $this->onHook($spot, $fxLong, $args, $priority);
    }

    /**
     * Delete all hooks for specified spot, priority and index.
     *
     * @param int|null $priority        filter specific priority, null for all
     * @param int      $priorityIsIndex filter by index instead of priority
     *
     * @return static
     */
    public function removeHook(string $spot, int $priority = null, bool $priorityIsIndex = false)
    {
        if ($priority !== null) {
            if ($priorityIsIndex) {
                $index = $priority;
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
     * @param int      $priorityIsIndex filter by index instead of priority
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
     * Execute all closures assigned to $hook_spot.
     *
     * @return mixed Array of responses indexed by hook indexes or value specified to breakHook
     */
    public function hook(string $spot, array $args = [], HookBreaker &$brokenBy = null)
    {
        $this->_rebindHooksIfCloned();

        $brokenBy = null;

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
