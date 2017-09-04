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

    /** @var array Helps debugTraceChange. */
    protected $_prev_bt = [];

    /**
     * Outputs message to STDERR.
     *
     * @codeCoverageIgnore - replaced with "echo" which can be intercepted by test-suite
     *
     * @param string $message
     */
    protected function _echo_stderr($message)
    {
        fwrite(STDERR, $message);
    }

    /**
     * Send some info to debug stream.
     *
     * @param bool|string $message
     * @param array       $context
     *
     * @return $this
     */
    public function debug($message = true, $context = [])
    {
        // using this to switch on/off the debug for this object
        if (is_bool($message)) {
            $this->debug = $message;

            return $this;
        }

        // if debug is enabled, then log it
        if ($this->debug) {
            if (isset($this->app) && $this->app instanceof \Psr\Log\LoggerInterface) {
                $this->app->log('debug', $message, $context);
            } else {
                $this->_echo_stderr('['.get_class($this)."]: $message\n");
            }
        }

        return $this;
    }

    /**
     * Output log message.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return $this
     */
    public function log($level, $message, $context = [])
    {
        if (isset($this->app) && $this->app instanceof \Psr\Log\LoggerInterface) {
            $this->app->log($level, $message, $context);
        } else {
            $this->_echo_stderr("$message\n");
        }

        return $this;
    }

    /**
     * Output message that needs to be acknowledged by application user. Make sure
     * that $context does not contain any sensitive information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return $this
     */
    public function userMessage($message, $context = [])
    {
        if (isset($this->app) && $this->app instanceof \atk4\core\AppUserNotificationInterface) {
            $this->app->userNotification($message, $context);
        } elseif (isset($this->app) && $this->app instanceof \Psr\Log\LoggerInterface) {
            $this->app->log('warning', 'Could not notify user about: '.$message, $context);
        } else {
            $this->_echo_stderr("Could not notify user about: $message\n");
        }

        return $this;
    }

    /**
     * Method designed to intercept one of the hardest-to-debug situations within Agile Toolkit.
     *
     * Suppose you define a hook and the hook needs to be called only once, but somehow it is
     * being called multiple times. You want to know where and how those calls come through.
     *
     * Place debugTraceChange inside your hook and give unique $trace identifier. If the method
     * is invoked through different call paths, this debug info will be logged.
     *
     * Do not leave this method in production code !!!
     *
     * @param string $trace
     */
    public function debugTraceChange($trace = 'default')
    {
        $bt = [];
        foreach (debug_backtrace() as $line) {
            if (isset($line['file'])) {
                $bt[] = $line['file'].':'.$line['line'];
            }
        }

        if (isset($this->_prev_bt[$trace]) && array_diff($this->_prev_bt[$trace], $bt)) {
            $d1 = array_diff($this->_prev_bt[$trace], $bt);
            $d2 = array_diff($bt, $this->_prev_bt[$trace]);

            $this->log('debug', 'Call path for '.$trace.' has diverged (was '.implode(', ', $d1).', now '.implode(', ', $d2).")\n");
        }

        $this->_prev_bt[$trace] = $bt;
    }
}
