<?php

declare(strict_types=1);

namespace Atk4\Core;

use Psr\Log\LogLevel;

trait DebugTrait
{
    /** @var bool Is debug enabled? */
    public $debug = false;

    /** @var array<string, array<int, string>> Helps debugTraceChange. */
    protected array $_previousTrace = [];

    /**
     * Outputs message to STDERR.
     */
    protected function _echoStderr(string $message): void
    {
        file_put_contents('php://stderr', $message, \FILE_APPEND);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed              $level
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (TraitUtil::hasAppScopeTrait($this) && $this->issetApp() && $this->getApp()->logger instanceof \Psr\Log\LoggerInterface) {
            $this->getApp()->logger->log($level, $message, $context);
        } else {
            $this->_echoStderr($message . "\n");
        }
    }

    /**
     * Detailed debug information.
     *
     * @param bool|string|\Stringable $message
     * @param array<mixed>            $context
     */
    public function debug($message, array $context = []): void
    {
        // using this to switch on/off the debug for this object
        if (is_bool($message)) {
            $this->debug = $message;

            return;
        }

        // if debug is enabled, then log it
        if ($this->debug) {
            if (!TraitUtil::hasAppScopeTrait($this) || !$this->issetApp() || !$this->getApp()->logger instanceof \Psr\Log\LoggerInterface) {
                $message = '[' . static::class . ']: ' . $message;
            }
            $this->log(LogLevel::DEBUG, $message, $context);
        }
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
     * Do not use this method in production code !!!
     */
    public function debugTraceChange(string $trace = 'default'): void
    {
        $bt = [];
        foreach (debug_backtrace() as $frame) {
            if (isset($frame['file'])) {
                $bt[] = $frame['file'] . ':' . $frame['line'];
            }
        }

        if (isset($this->_previousTrace[$trace]) && array_diff($this->_previousTrace[$trace], $bt)) {
            $d1 = array_diff($this->_previousTrace[$trace], $bt);
            $d2 = array_diff($bt, $this->_previousTrace[$trace]);

            $this->log(LogLevel::DEBUG, 'Call path for ' . $trace . ' has diverged (was ' . implode(', ', $d1) . ', now ' . implode(', ', $d2) . ")\n");
        }

        $this->_previousTrace[$trace] = $bt;
    }

    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
}
