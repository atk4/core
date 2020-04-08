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

    /** @var \Throwable */
    public $exception;

    /** @var \Throwable|null */
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
            return get_class($this->exception).' ['.$this->exception->getCode().'] Error: '.$this->_($this->exception->getMessage());
        }
    }

    protected function replaceTokens(array $tokens, string $text): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    protected function parseCallTraceObject($call): array
    {
        $parsed = [
            'line'             => (string) ($call['line'] ?? ''),
            'file'             => (string) ($call['file'] ?? ''),
            'class'            => $call['class'] ?? null,
            'object'           => $call['object'] ?? null,
            'function'         => $call['function'] ?? null,
            'args'             => $call['args'] ?? [],
            'object_formatted' => null,
            'file_formatted'   => null,
            'line_formatted'   => null,
        ];

        $parsed['file_formatted'] = str_pad(substr($parsed['file'], -40), 40, ' ', STR_PAD_LEFT);
        $parsed['line_formatted'] = str_pad($parsed['line'] ?? '', 4, ' ', STR_PAD_LEFT);

        if (null !== $parsed['object']) {
            $parsed['object_formatted'] = $parsed['object']->name ?? get_class($parsed['object']);
        }

        return $parsed;
    }

    public static function toSafeString($val): string
    {
        if (is_object($val) && !$val instanceof \Closure) {
            return isset($val->_trackableTrait)
                ? get_class($val).' ('.$val->name.')'
                : 'Object '.get_class($val);
        }

        return (string) json_encode($val);
    }

    protected static function getClassShortName(\Throwable $exception): string
    {
        return preg_replace('/.*\\\\/', '', get_class($exception));
    }

    /**
     * @return string
     */
    protected function getExceptionTitle(): string
    {
        return $this->exception instanceof Exception
            ? $this->exception->getCustomExceptionTitle()
            : static::getClassShortName($this->exception).' Error';
    }

    /**
     * @return string
     */
    protected function getExceptionName(): string
    {
        return $this->exception instanceof Exception
            ? $this->exception->getCustomExceptionName()
            : get_class($this->exception);
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

        if (!$shorten || $this->parent_exception === null) {
            return $trace;
        }

        $parent_trace = $this->parent_exception instanceof Exception
            ? $this->parent_exception->getMyTrace()
            : $this->parent_exception->getTrace();
        $parent_trace = array_combine(range(count($parent_trace) - 1, 0, -1), $parent_trace);

        $both_atk = $this->exception instanceof Exception && $this->parent_exception instanceof Exception;
        $c = min(count($trace), count($parent_trace));
        for ($i = 0; $i < $c; $i++) {
            $cv = $trace[$i];
            $pv = $parent_trace[$i];

            if (($cv['line'] ?? null) === ($pv['line'] ?? null)
                    && ($cv['file'] ?? null) === ($pv['file'] ?? null)
                    && ($cv['class'] ?? null) === ($pv['class'] ?? null)
                    && (!$both_atk || ($cv['object'] ?? null) === ($pv['object'] ?? null))
                    && ($cv['function'] ?? null) === ($pv['function'] ?? null)
                    && (!$both_atk || ($cv['args'] ?? null) === ($pv['args'] ?? null))) {
                unset($trace[$i]);
            } else {
                break;
            }
        }

        return $trace;
    }

    public function _($message, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->adapter
            ? $this->adapter->_($message, $parameters, $domain, $locale)
            : Translator::instance()->_($message, $parameters, $domain, $locale);
    }
}
