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

    private function _unbindThisFromHookIfNotUsed(\Closure $fx): \Closure
    {
        $fxThis = (new \ReflectionFunction($fx))->getClosureThis();
        if ($fxThis === null) {
            return $fx;
        }

        // detect if $this is unused, there is not better detection than php warning
        // see https://stackoverflow.com/questions/63692512/how-to-detect-if-this-is-used-in-closure
        $hasThis = false;
        set_error_handler(function ($errNo, $errStr) use (&$hasThis) {
            if (preg_match(
                \PHP_MAJOR_VERSION === 7
                    ? '~^Unbinding \$this of (?:a )?(?:method|closure) is deprecated$~s'
                    : '~^Cannot unbind \$this of (?:method|closure using \$this)$~s',
                $errStr
            )) {
                $hasThis = true;
            } else {
                throw (new Exception('Unexpected error'))
                    ->addMoreInfo('error', $errStr);
            }
        });
        $fxUnbound = \Closure::bind($fx, null);
        restore_error_handler();

        // there is no warning in PHP 7.3, so detect $this from code, remove once PHP 7.3 support is dropped
        if (\PHP_MAJOR_VERSION <= 7 && \PHP_MINOR_VERSION <= 3) {
            $funcRefl = new \ReflectionFunction($fx);
            if ($funcRefl->getEndLine() === $funcRefl->getStartLine()) {
                throw new \atk4\ui\Exception('Closure body to extract must be on separate lines');
            }

            $funcCode = implode("\n", array_slice(
                explode("\n", file_get_contents($funcRefl->getFileName())),
                $funcRefl->getStartLine(),
                $funcRefl->getEndLine() - $funcRefl->getStartLine() - 1
            ));

            if (str_contains($funcCode, '$this')) {
                $hasThis = true;
            }
        }

        return $hasThis ? $fx : $fxUnbound;
    }

    /**
     * Add another callback to be executed during hook($hook_spot);.
     *
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string   $spot     Hook identifier to bind on
     * @param \Closure $fx       Will be called on hook()
     * @param array    $args     Arguments are passed to $fx
     * @param int      $priority Lower priority is called sooner
     *
     * @return int Index under which the hook was added
     */
    public function onHook(string $spot, \Closure $fx = null, array $args = [], int $priority = 5)
    {
        $this->_rebindHooksIfCloned();

        $fx = $this->_unbindThisFromHookIfNotUsed($fx);

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
     * Delete all hooks for specified spot, priority and index.
     *
     * @param string   $spot            Hook identifier
     * @param int|null $priority        Filter specific priority, null for all
     * @param int      $priorityIsIndex Filter by index instead of priority
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
     * @param string   $spot            Hook identifier
     * @param int|null $priority        Filter specific priority, null for all
     * @param int      $priorityIsIndex Filter by index instead of priority
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
     * @param string $spot Hook identifier
     * @param array  $args Additional arguments to closures
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
