<?php

namespace atk4\core;

trait DebugTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_debugTrait = true;

    /** @var bool Is debug enabled? */
    public $debug = null;

    /**
     * Returns true if debug mode is enabled.
     *
     * @return bool
     */
    protected function isDebugEnabled()
    {
        if ($this->debug === false || $this->debug === true) {
            return $this;
        }

        return isset($this->app) && isset($this->app->debug) && $this->app->debug;
    }

    /**
     * Send some info to debug stream.
     *
     * @param bool  $msg
     * @param array $extra_info
     *
     * @return $this
     */
    public function debug($msg = true, $extra_info = [])
    {
        if (is_bool($msg)) {
            // using this to switch on/off the debug for this object
            $this->debug = $msg;

            return $this;
        }

        if ($this->isDebugEnabled()) {
            if (
                isset($this->app)
                && (
                    (isset($this->app->_dynamicMethodTrait) && $this->app->hasMethod('outputDebug'))
                    || method_exists($this->app, 'outputDebug')
                )
            ) {
                $this->app->outputDebug($msg, $extra_info);
            } else {
                fwrite(STDERR, '['.get_class($this)."]: $msg\n");
            }
        }

        return $this;
    }

    public $_prev_bt = [];

    public function debugTraceChange($trace = 'default')
    {
        if ($this->isDebugEnabled()) {
            $bt = [];
            foreach (debug_backtrace() as $line) {
                if (isset($line['file'])) {
                    $bt[] = $line['file'].':'.$line['line'];
                }
            }

            if (isset($this->_prev_bt[$trace]) && array_diff($this->_prev_bt[$trace], $bt)) {
                $d1 = array_diff($this->_prev_bt[$trace], $bt);
                $d2 = array_diff($bt, $this->_prev_bt[$trace]);

                $this->debug('Call path for '.$trace.' has diverged (was '.implode(', ', $d1).', now '.implode(', ', $d2).")\n");
            }

            $this->_prev_bt[$trace] = $bt;
        }
    }
}
