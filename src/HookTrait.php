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
     * @deprecated use onHook instead
     */
    public function addHook($spot, $fx, $args = null, $priority = null)
    {
        return $this->onHook(...func_get_args());
    }

    /**
     * Add another callback to be executed during hook($hook_spot);.
     *
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string          $spot     Hook identifier to bind on
     * @param object|callable $fx       Will be called on hook()
     * @param array           $args     Arguments are passed to $fx
     * @param int             $priority Lower priority is called sooner
     *
     * @return $this
     */
    public function onHook($spot, $fx = null, $args = [], $priority = 5)
    {
        $fx = $fx ?: $this;

        $args = (array) $args;

        // multiple hooks can be linked
        if (is_string($spot) && strpos($spot, ',') !== false) {
            $spot = explode(',', $spot);
        }
        if (is_array($spot)) {
            foreach ($spot as $h) {
                $this->onHook($h, $fx, $args, $priority);
            }

            return $this;
        }

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

        if ($priority >= 0) {
            $this->hooks[$spot][$priority][] = [$fx, $args];
        } else {
            array_unshift($this->hooks[$spot][$priority], [$fx, $args]);
        }

        return $this;
    }

    /**
     * Delete all hooks for specified spot.
     *
     * @param string $spot Hook identifier to bind on
     *
     * @return $this
     */
    public function removeHook($spot)
    {
        unset($this->hooks[$spot]);

        return $this;
    }

    /**
     * Returns true if at least one callback is defined for this hook.
     *
     * @param string $spot Hook identifier
     *
     * @return bool
     */
    public function hookHasCallbacks($spot)
    {
        return isset($this->hooks[$spot]);
    }

    /**
     * Execute all callables assigned to $hook_spot.
     *
     * @param string $spot Hook identifier
     * @param array  $args Additional arguments to callables
     *
     * @throws Exception
     *
     * @return mixed Array of responses or value specified to breakHook
     */
    public function hook($spot, $args = null)
    {
        $args = (array) $args;

        $return = [];

        try {
            if (
                isset($this->hooks[$spot])
                && is_array($this->hooks[$spot])
            ) {
                krsort($this->hooks[$spot]); // lower priority is called sooner
                $hookBackup = $this->hooks[$spot];
                while ($_data = array_pop($this->hooks[$spot])) {
                    foreach ($_data as &$data) {
                        $return[] = call_user_func_array(
                            $data[0],
                            array_merge(
                                [$this],
                                $args,
                                $data[1]
                            )
                        );
                    }
                }

                $this->hooks[$spot] = $hookBackup;
            }
        } catch (HookBreaker $e) {
            $this->hooks[$spot] = $hookBackup;

            return $e->return_value;
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
