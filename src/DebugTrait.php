<?php

namespace atk4\core;

use Psr\Log\LogLevel;

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
        file_put_contents('php://stderr', $message);
    }

    /**
     * Send some info to debug stream.
     *
     * @param bool|string $message
     * @param array       $context
     *
     * @return $this
     */
    public function debug($message = true, array $context = [])
    {
        // using this to switch on/off the debug for this object
        if (is_bool($message)) {
            $this->debug = $message;

            return $this;
        }

        // if debug is enabled, then log it
        if ($this->debug) {
            if (!isset($this->app) || !isset($this->app->logger) || !$this->app->logger instanceof \Psr\Log\LoggerInterface) {
                $message = '['.get_class($this).']: '.$message;
            }
            $this->log(LogLevel::DEBUG, $message, $context);
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
    public function log($level, $message, array $context = [])
    {
        if (isset($this->app) && isset($this->app->logger) && $this->app->logger instanceof \Psr\Log\LoggerInterface) {
            $this->app->logger->log($level, $message, $context);
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

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
}
