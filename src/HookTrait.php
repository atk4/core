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
    public function addHook($hookSpot, $fx, $args = null, $priority = null)
    {
        return $this->onHook(...func_get_args());
    }

    /**
     * Add another callback to be executed during hook($hook_spot);.
     *
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string          $hookSpot Hook identifier to bind on
     * @param object|callable $fx       Will be called on hook()
     * @param array           $args     Arguments are passed to $callable
     * @param int             $priority Lower priority is called sooner
     *
     * @return $this
     */
    public function onHook($hookSpot, $fx, $args = null, $priority = null)
    {

        // Set defaults
        if (is_null($args)) {
            $args = [];
        } elseif (!is_array($args)) {
            throw new Exception(['Incorrect arguments for onHook', 'args' => $args]);
        }
        if (is_null($priority)) {
            $priority = 5;
        }

        // multiple hooks can be linked
        if (is_string($hookSpot) && strpos($hookSpot, ',') !== false) {
            $hookSpot = explode(',', $hookSpot);
        }
        if (is_array($hookSpot)) {
            foreach ($hookSpot as $h) {
                $this->onHook($h, $fx, $args, $priority);
            }

            return $this;
        }

        // short for onHook('test', $this); to call $this->test();
        if (!is_callable($fx)) {
            if (is_object($fx)) {
                if (isset($fx->_dynamicMethodTrait)) {
                    if (!$fx->hasMethod($hookSpot)) {
                        throw new Exception([
                            '$fx should be a valid callback',
                            'callable' => $fx,
                        ]);
                    }
                } else {
                    if (!method_exists($fx, $hookSpot)) {
                        throw new Exception([
                            '$callable should be a valid callback',
                            'callable' => $fx,
                        ]);
                    }
                }
                $fx = [$fx, $hookSpot];
            } else {
                throw new Exception([
                    '$fx should be a valid callback',
                    'callable' => $fx,
                ]);
            }
        }

        if (!isset($this->hooks[$hookSpot][$priority])) {
            $this->hooks[$hookSpot][$priority] = [];
        }

        if ($priority >= 0) {
            $this->hooks[$hookSpot][$priority][] = [$fx, $args];
        } else {
            array_unshift($this->hooks[$hookSpot][$priority], [$fx, $args]);
        }

        return $this;
    }

    /**
     * Delete all hooks for specified spot.
     *
     * @param string $hookSpot Hook identifier to bind on
     *
     * @return $this
     */
    public function removeHook($hookSpot)
    {
        unset($this->hooks[$hookSpot]);

        return $this;
    }

    /**
     * Returns true if at least one callback is defined for this hook.
     *
     * @param string $hookSpot Hook identifier
     *
     * @return bool
     */
    public function hookHasCallbacks($hookSpot)
    {
        return isset($this->hooks[$hookSpot]);
    }

    /**
     * Execute all callables assigned to $hook_spot.
     *
     * @param string $hookSpot Hook identifier
     * @param array  $args     Additional arguments to callables
     *
     * @throws Exception
     *
     * @return mixed Array of responses or value specified to breakHook
     */
    public function hook($hookSpot, $args = null)
    {
        if (is_null($args)) {
            $args = [];
        } elseif (!is_array($args)) {
            throw new Exception([
                'Arguments for callbacks should be passed as array',
                'arg' => $args,
            ]);
        }

        $return = [];

        try {
            if (
                isset($this->hooks[$hookSpot])
                && is_array($this->hooks[$hookSpot])
            ) {
                krsort($this->hooks[$hookSpot]); // lower priority is called sooner
                $hookBackup = $this->hooks[$hookSpot];
                while ($_data = array_pop($this->hooks[$hookSpot])) {
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

                $this->hooks[$hookSpot] = $hookBackup;
            }
        } catch (HookBreaker $e) {
            $this->hooks[$hookSpot] = $hookBackup;

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
