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
     * Add another callback to be executed during hook($hook_spot);.
     *
     * If priority is negative, then hooks will be executed in reverse order.
     *
     * @param string          $hook_spot Hook identifier to bind on
     * @param object|callable $callable  Will be called on hook()
     * @param array           $arguments Arguments are passed to $callable
     * @param int             $priority  Lower priority is called sooner
     *
     * @return $this
     */
    public function addHook($hook_spot, $callable, $arguments = null, $priority = null)
    {

        // Set defaults
        if (is_null($arguments)) {
            $arguments = [];
        } elseif (!is_array($arguments)) {
            throw new Exception(['Incorrect arguments for addHook', 'args' => $arguments]);
        }
        if (is_null($priority)) {
            $priority = 5;
        }

        // multiple hooks can be linked
        if (is_string($hook_spot) && strpos($hook_spot, ',') !== false) {
            $hook_spot = explode(',', $hook_spot);
        }
        if (is_array($hook_spot)) {
            foreach ($hook_spot as $h) {
                $this->addHook($h, $callable, $arguments, $priority);
            }

            return $this;
        }

        // short for addHook('test', $this); to call $this->test();
        if (!is_callable($callable)) {
            if (is_object($callable)) {
                if (isset($callable->_dynamicMethodTrait)) {
                    if (!$callable->hasMethod($hook_spot)) {
                        throw new Exception([
                            '$callable should be a valid callback',
                            'callable' => $callable,
                        ]);
                    }
                } else {
                    if (!method_exists($callable, $hook_spot)) {
                        throw new Exception([
                            '$callable should be a valid callback',
                            'callable' => $callable,
                        ]);
                    }
                }
                $callable = [$callable, $hook_spot];
            } else {
                throw new Exception([
                    '$callable should be a valid callback',
                    'callable' => $callable,
                ]);
            }
        }

        if (!isset($this->hooks[$hook_spot][$priority])) {
            $this->hooks[$hook_spot][$priority] = [];
        }

        if ($priority >= 0) {
            $this->hooks[$hook_spot][$priority][] = [$callable, $arguments];
        } else {
            array_unshift($this->hooks[$hook_spot][$priority], [$callable, $arguments]);
        }

        return $this;
    }

    /**
     * Delete all hooks for specified spot.
     *
     * @param string $hook_spot Hook identifier to bind on
     *
     * @return $this
     */
    public function removeHook($hook_spot)
    {
        unset($this->hooks[$hook_spot]);

        return $this;
    }

    /**
     * Returns true if at least one callback is defined for this hook.
     *
     * @param string $hook_spot Hook identifier
     *
     * @return bool
     */
    public function hookHasCallbacks($hook_spot)
    {
        return isset($this->hooks[$hook_spot]);
    }

    /**
     * Execute all callables assigned to $hook_spot.
     *
     * @param string $hook_spot Hook identifier
     * @param array  $arg       Additional arguments to callables
     *
     * @return mixed Array of responses or value specified to breakHook
     */
    public function hook($hook_spot, $arg = null)
    {
        if (is_null($arg)) {
            $arg = [];
        } elseif (!is_array($arg)) {
            throw new Exception([
                'Arguments for callbacks should be passed as array',
                'arg' => $arg,
            ]);
        }

        $return = [];

        try {
            if (
                isset($this->hooks[$hook_spot])
                && is_array($this->hooks[$hook_spot])
            ) {
                krsort($this->hooks[$hook_spot]); // lower priority is called sooner
                $hook_backup = $this->hooks[$hook_spot];
                while ($_data = array_pop($this->hooks[$hook_spot])) {
                    foreach ($_data as &$data) {
                        $return[] = call_user_func_array(
                            $data[0],
                            array_merge(
                                [$this],
                                $arg,
                                $data[1]
                            )
                        );
                    }
                }

                $this->hooks[$hook_spot] = $hook_backup;
            }
        } catch (HookBreaker $e) {
            $this->hooks[$hook_spot] = $hook_backup;

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
     */
    public function breakHook($return)
    {
        throw new HookBreaker($return);
    }
}
