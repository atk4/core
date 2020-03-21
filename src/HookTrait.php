<?php

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
     * @deprecated use onHook instead
     */
    public function addHook($spot, $fx, array $args = null, int $priority = null)
    {
        return $this->onHook($spot, $fx, $args, $priority ?? 0);
    }

    /**
     * Add another callback to be executed during hook($hook_spot);.
     *
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string|string[]      $spot     Hook identifier to bind on
     * @param object|callable|null $fx       Will be called on hook()
     * @param array                $args     Arguments are passed to $fx
     * @param int                  $priority Lower priority is called sooner
     *
     * @return int|int[] Index under which the hook was added
     */
    public function onHook($spot, $fx = null, array $args = [], int $priority = 5)
    {
        $fx = $fx ?: $this;

        // multiple hooks can be linked
        if (is_string($spot) && strpos($spot, ',') !== false) {
            $spot = explode(',', $spot);
        }
        if (is_array($spot)) {
            $indexes = [];
            foreach ($spot as $k => $h) {
                $indexes[$k] = $this->onHook($h, $fx, $args, $priority);
            }

            return $indexes;
        }
        $spot = (string) $spot;

        // short for onHook('test', $this); to call $this->test();
        if (!is_callable($fx)) {
            $valid = false;
            if (is_object($fx)) {
                $valid = (isset($fx->_dynamicMethodTrait) && $fx->hasMethod($spot)) || method_exists($fx, $spot);
            }

            if (!$valid) {
                throw new Exception([
                    '$fx should be a valid callback',
                    'fx' => $fx,
                ]);
            }

            $fx = [$fx, $spot];
        }

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
     * @param int|null $priorityIsIndex Filter by index instead of priority
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
     * @param int|null $priorityIsIndex Filter by index instead of priority
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
     * Execute all callables assigned to $hook_spot.
     *
     * @param string $spot Hook identifier
     * @param array  $args Additional arguments to callables
     *
     * @throws Exception
     *
     * @return mixed Array of responses indexed by hook indexes or value specified to breakHook
     */
    public function hook(string $spot, array $args = [])
    {
        $return = [];

        if (isset($this->hooks[$spot])) {
            krsort($this->hooks[$spot]); // lower priority is called sooner
            $hookBackup = $this->hooks[$spot];

            try {
                while ($_data = array_pop($this->hooks[$spot])) {
                    foreach ($_data as $index => &$data) {
                        $return[$index] = call_user_func_array(
                            $data[0],
                            array_merge(
                                [$this],
                                $args,
                                $data[1]
                            )
                        );
                    }
                }
                unset($data);

                $this->hooks[$spot] = $hookBackup;
            } catch (HookBreaker $e) {
                $this->hooks[$spot] = $hookBackup;

                return $e->return_value;
            }
        }

        return $return;
    }

    /**
     * When called from inside a hook callable, will stop execution of other
     * callables on the same hook. The passed argument will be returned by the
     * hook method.
     *
     * @param mixed $return What would hook() return?
     *
     * @throws HookBreaker
     */
    public function breakHook($return)
    {
        throw new HookBreaker($return);
    }
}
