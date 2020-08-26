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

    /** @var array Configured hooks (callbacks). */
    protected $hooks = [];

    /** @var int Next hook index counter. */
    private $_hookIndexCounter = 0;

    /** @var string[] */
    private $_hookActiveSpots = [];

    private function _hookRequireInactive(string $spot): void
    {
        if ($this->_hookActiveSpots[$spot] ?? false) {
            throw (new Exception('Hook spot must be inactive for requested operation, but it is already executing'))
                ->addMoreInfo('object', $this)
                ->addMoreInfo('spot', $spot);
        }
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
        $this->_hookRequireInactive($spot);

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
        $this->_hookRequireInactive($spot);

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
        $brokenBy = null;

        $this->_hookRequireInactive($spot);

        $return = [];
        if (isset($this->hooks[$spot])) {
            ksort($this->hooks[$spot]); // lower priority is called sooner

            try {
                $this->_hookActiveSpots[$spot] = true;

                foreach ($this->hooks[$spot] as $datas) {
                    foreach ($datas as $index => $data) {
                        $return[$index] = $data[0](...array_merge(
                            [$this],
                            $args,
                            $data[1]
                        ));
                    }
                }
            } catch (HookBreaker $e) {
                $brokenBy = $e;

                return $e->getReturnValue();
            } finally {
                $this->_hookActiveSpots[$spot] = false;
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
