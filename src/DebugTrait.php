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
     * Send some info to debug stream.
     *
     * @param bool  $msg
     * @param array $context
     *
     * @return $this
     */
    public function debug($msg = true, $context = [])
    {
        if (is_bool($msg)) {
            // using this to switch on/off the debug for this object
            $this->debug = $msg;

            return $this;
        }

        if ($this->debug) {
            if (isset($this->app) && $this->app instanceof \Psr\Log\LoggerInterface) {
                $this->app->log('debug', $msg, $context);
            } else {
                echo '['.get_class($this)."]: $msg\n";
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
