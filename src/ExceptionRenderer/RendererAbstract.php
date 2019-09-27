<?php

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

abstract class RendererAbstract
{
    /** @var \Throwable */
    public $exception;

    /** @var string */
    public $output = '';

    /** @var bool */
    public $is_atk_exception = false;

    public function __construct($exception)
    {
        $this->exception = $exception;
        $this->is_atk_exception = $exception instanceof Exception;
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
        $this->processAll();

        return $this->output;
    }

    protected function replaceTokens(array $tokens, string $text): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    protected function parseCallTraceObject($call): array
    {
        $parsed = [
            'line'             => $call['line'] ?? '',
            'file'             => $call['file'] ?? '',
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
            if (isset($val->_trackableTrait)) {
                return get_class($val).' ('.$val->name.')';
            }

            return 'Object '.get_class($val);
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
        $title = $this->is_atk_exception
            ? $this->exception->getCustomExceptionTitle()
            : static::getClassShortName($this->exception).' Error';

        return $title;
    }

    /**
     * @return string
     */
    protected function getExceptionName(): string
    {
        $class = $this->is_atk_exception
            ? $this->exception->getCustomExceptionName()
            : get_class($this->exception);

        return $class;
    }
}
