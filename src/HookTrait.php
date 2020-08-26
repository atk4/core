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

    private function hookResolveReflectionType(\ReflectionType $type, ?string $selfClass, ?string $staticClass): string
    {
        $n = $type->getName();

        if ($n === 'self' || $n === 'static') {
            if ($selfClass === null || $staticClass === null) {
                throw new \Exception('Self or static used, but class is not defined');
            }

            if ($n === 'self') {
                return $selfClass;
            } else {
                return $staticClass;
            }
        }

        return $n;;
    }

    private function hookCheckIfParameterTypeMatchesStub(
        ?\ReflectionType $stubType,
        ?\ReflectionType $hookType,
        bool $isReturn,
        ?string $stubSelfClass,
        ?string $stubStaticClass,
        ?string $hookSelfClass,
        ?string $hookStaticClass
    ): void {
        if ($stubType === null && $hookType === null) {
            return;
        }

        if ($isReturn) {
            if ($stubType === null) {
                return;
            }

            [$stubType, $hookType] = [$hookType, $stubType];
        }

        if ($stubType === null) { // stub has less params defined
            return;
        } elseif ($hookType === null) {
            return;
            throw new Exception('Missing parameter');
        }

        if ($stubType->allowsNull() && !$hookType->allowsNull()) {
            throw new Exception('Must be nullable');
        }

        $stubResolved = $this->hookResolveReflectionType($stubType, $stubSelfClass, $stubStaticClass);
        $hookResolved = $this->hookResolveReflectionType($hookType, $hookSelfClass, $hookStaticClass);

        if (($stubType->isBuiltin() || $hookType->isBuiltin()) && $stubResolved !== $hookResolved) {
            if ($hookResolved === 'object') {
                return;
            }

            throw (new Exception('Builtin type must always match'))
                ->addMoreInfo('stub', $stubResolved)
                ->addMoreInfo('hook', $hookResolved);
        }

        // we do not want to check what php will do on call
        // but instead if declared type on onHook() side can be better
        if (!is_a($hookResolved, $stubResolved, true)) {
            throw (new Exception('Type mismatch'))
                ->addMoreInfo('stub', $stubResolved)
                ->addMoreInfo('hook', $hookResolved);
        }
    }

    private function hookCheckIfFxMatchesStub(\Closure $stubFx, \Closure $hookFx): void
    {
        $stubFxRef = new \ReflectionFunction($stubFx);
        $hookFxRef = new \ReflectionFunction($hookFx);
        unset($stubFx, $hookFx);

        $stubSelfClass = $stubFxRef->getClosureScopeClass() !== null ? $stubFxRef->getClosureScopeClass()->getName() : null;
        $stubStaticClass = $stubFxRef->getClosureThis() !== null ? get_class($stubFxRef->getClosureThis()) : null;

        $hookSelfClass = $hookFxRef->getClosureScopeClass() !== null ? $hookFxRef->getClosureScopeClass()->getName() : null;
        $hookStaticClass = $hookFxRef->getClosureThis() !== null ? get_class($hookFxRef->getClosureThis()) : null;

        $stubFxParams = $stubFxRef->getParameters();
        $hookFxParams = $hookFxRef->getParameters();
        for ($i = 0; $i < count($stubFxParams) || $i < count($hookFxParams); ++$i) {
            $this->hookCheckIfParameterTypeMatchesStub(
                isset($stubFxParams[$i]) ? $stubFxParams[$i]->getType() : null,
                isset($hookFxParams[$i]) ? $hookFxParams[$i]->getType() : null,
                false,
                $stubSelfClass,
                $stubStaticClass,
                $hookSelfClass,
                $hookStaticClass,
            );
        }
        $this->hookCheckIfParameterTypeMatchesStub(
            $stubFxRef->getReturnType(),
            $hookFxRef->getReturnType(),
            true,
            $stubSelfClass,
            $stubStaticClass,
            $hookSelfClass,
            $hookStaticClass,
        );
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

        $return = [];

        if (isset($this->hooks[$spot])) {
            if (isset($args['hookStub'])) {
                $stubFx = $args['hookStub'];
                unset($args['hookStub']);
            } else {
                $stubFx = null;

                // debug
                $stubFx = eval('return function(' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]['class'] . ' $x'.') {};');
            }

            krsort($this->hooks[$spot]); // lower priority is called sooner
            $hookBackup = $this->hooks[$spot];

            try {
                while ($_data = array_pop($this->hooks[$spot])) {
                    foreach ($_data as $index => [$hookFx, $hookArgs]) {
                        if ($stubFx !== null) {
                            $this->hookCheckIfFxMatchesStub($stubFx, $hookFx);
                        }

                        $return[$index] = $hookFx($this, ...array_merge(
                            $args,
                            $hookArgs
                        ));
                    }
                }
            } catch (HookBreaker $e) {
                $brokenBy = $e;

                return $e->getReturnValue();
            } finally {
                $this->hooks[$spot] = $hookBackup;
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
