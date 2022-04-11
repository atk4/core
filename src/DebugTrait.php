<?php

declare(strict_types=1);

namespace Atk4\Core;

use Psr\Log\LogLevel;

trait DebugTrait
{
    /** @var bool Is debug enabled? */
    public $debug = false;

    /** @var array Helps debugTraceChange. */
    protected $_prevTrace = [];

    /**
     * Outputs message to STDERR.
     *
     * @codeCoverageIgnore - replaced with "echo" which can be intercepted by test-suite
     */
    protected function _echoStderr(string $message): void
    {
        file_put_contents('php://stderr', $message, \FILE_APPEND);
    }

    /**
     * Send some info to debug stream.
     *
     * @param bool|string $message
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
            if (!TraitUtil::hasAppScopeTrait($this) || !$this->issetApp() || !$this->getApp()->logger instanceof \Psr\Log\LoggerInterface) {
                $message = '[' . static::class . ']: ' . $message;
            }
            $this->log(LogLevel::DEBUG, $message, $context);
        }

        return $this;
    }

    /**
     * Output log message.
     *
     * @param mixed  $level
     * @param string $message
     *
     * @return $this
     */
    public function log($level, $message, array $context = [])
    {
        if (TraitUtil::hasAppScopeTrait($this) && $this->issetApp() && $this->getApp()->logger instanceof \Psr\Log\LoggerInterface) {
            $this->getApp()->logger->log($level, $message, $context);
        } else {
            $this->_echoStderr($message . "\n");
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
     */
    public function debugTraceChange(string $trace = 'default'): void
    {
        $bt = [];
        foreach (debug_backtrace() as $frame) {
            if (isset($frame['file'])) {
                $bt[] = $frame['file'] . ':' . $frame['line'];
            }
        }

        if (isset($this->_prevTrace[$trace]) && array_diff($this->_prevTrace[$trace], $bt)) {
            $d1 = array_diff($this->_prevTrace[$trace], $bt);
            $d2 = array_diff($bt, $this->_prevTrace[$trace]);

            $this->log('debug', 'Call path for ' . $trace . ' has diverged (was ' . implode(', ', $d1) . ', now ' . implode(', ', $d2) . ")\n");
        }

        $this->_prevTrace[$trace] = $bt;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     *
     * @return $this
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     *
     * @return $this
     */
    public function alert($message, array $context = [])
    {
        return $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     *
     * @return $this
     */
    public function critical($message, array $context = [])
    {
        return $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     *
     * @return $this
     */
    public function error($message, array $context = [])
    {
        return $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     *
     * @return $this
     */
    public function warning($message, array $context = [])
    {
        return $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     *
     * @return $this
     */
    public function notice($message, array $context = [])
    {
        return $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     *
     * @return $this
     */
    public function info($message, array $context = [])
    {
        return $this->log(LogLevel::INFO, $message, $context);
    }
}
