<?php

declare(strict_types=1);

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;
use atk4\core\TranslatableTrait;
use atk4\core\Translator\ITranslatorAdapter;
use atk4\core\Translator\Translator;

abstract class RendererAbstract
{
    use TranslatableTrait;

    /** @var \Throwable|Exception */
    public $exception;

    /** @var \Throwable|Exception|null */
    public $parent_exception;

    /** @var string */
    public $output = '';

    /** @var ITranslatorAdapter|null */
    public $adapter;

    public function __construct(\Throwable $exception, ITranslatorAdapter $adapter = null, \Throwable $parent_exception = null)
    {
        $this->adapter = $adapter;
        $this->exception = $exception;
        $this->parent_exception = $parent_exception;
    }

    abstract protected function processHeader(): void;

    abstract protected function processParams(): void;

    abstract protected function processSolutions(): void;

    abstract protected function processStackTrace(): void;

    abstract protected function processStackTraceInternal(): void;

    abstract protected function processPreviousException(): void;

    protected function processAll(): void
    {
        $this->processHeader();
        $this->processParams();
        $this->processSolutions();
        $this->processStackTrace();
        $this->processPreviousException();
    }

    public function __toString(): string
    {
        try {
            $this->processAll();

            return $this->output;
        } catch (\Throwable $e) {
            // fallback if Exception occur in renderer
            return get_class($this->exception) . ' [' . $this->exception->getCode() . '] Error: ' . $this->_($this->exception->getMessage());
        }
    }

    protected function replaceTokens(array $tokens, string $text): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    protected function parseStackTraceCall(array $call): array
    {
        $parsed = [
            'line' => (string) ($call['line'] ?? ''),
            'file' => (string) ($call['file'] ?? ''),
            'class' => $call['class'] ?? null,
            'object' => $call['object'] ?? null,
            'function' => $call['function'] ?? null,
            'args' => $call['args'] ?? [],
            'object_formatted' => null,
            'file_formatted' => null,
            'line_formatted' => null,
        ];

        $parsed['file_formatted'] = str_pad(substr($parsed['file'], -40), 40, ' ', STR_PAD_LEFT);
        $parsed['line_formatted'] = str_pad($parsed['line'] ?? '', 4, ' ', STR_PAD_LEFT);

        if ($parsed['object'] !== null) {
            $parsed['object_formatted'] = $parsed['object']->name ?? get_class($parsed['object']);
        }

        return $parsed;
    }

    public static function toSafeString($val): string
    {
        if (is_object($val) && !$val instanceof \Closure) {
            return isset($val->_trackableTrait)
                ? get_class($val) . ' (' . $val->name . ')'
                : 'Object ' . get_class($val);
        }

        return (string) json_encode($val);
    }

    protected function getExceptionTitle(): string
    {
        return $this->exception instanceof Exception
            ? $this->exception->getCustomExceptionTitle()
            : 'Critical Error';
    }

    /**
     * Returns stack trace and reindex it from the first call. If shortening is allowed,
     * shorten the stack trace if it starts with the parent one.
     */
    protected function getStackTrace(bool $shorten): array
    {
        $trace = $this->exception instanceof Exception
            ? $this->exception->getMyTrace()
            : $this->exception->getTrace();
        $trace = array_combine(range(count($trace) - 1, 0, -1), $trace);

        if ($shorten && $this->parent_exception !== null) {
            $parent_trace = $this->parent_exception instanceof Exception
                ? $this->parent_exception->getMyTrace()
                : $this->parent_exception->getTrace();
            $parent_trace = array_combine(range(count($parent_trace) - 1, 0, -1), $parent_trace);
        } else {
            $parent_trace = [];
        }

        $both_atk = $this->exception instanceof Exception && $this->parent_exception instanceof Exception;
        $c = min(count($trace), count($parent_trace));
        for ($i = 0; $i < $c; ++$i) {
            $cv = $this->parseStackTraceCall($trace[$i]);
            $pv = $this->parseStackTraceCall($parent_trace[$i]);

            if ($cv['line'] === $pv['line']
                    && $cv['file'] === $pv['file']
                    && $cv['class'] === $pv['class']
                    && (!$both_atk || $cv['object'] === $pv['object'])
                    && $cv['function'] === $pv['function']
                    && (!$both_atk || $cv['args'] === $pv['args'])) {
                unset($trace[$i]);
            } else {
                break;
            }
        }

        return $trace;
    }

    public function _($message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->adapter
            ? $this->adapter->_($message, $parameters, $domain, $locale)
            : Translator::instance()->_($message, $parameters, $domain, $locale);
    }
}
