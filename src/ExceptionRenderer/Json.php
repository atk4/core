<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Json extends RendererAbstract
{
    /** @var array */
    protected $json = [
        'success' => false,
        'code' => 0,
        'message' => '',
        'title' => '',
        'class' => '',
        'params' => [],
        'solution' => [],
        'trace' => [],
        'previous' => [],
    ];

    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = get_class($this->exception);

        $this->json['code'] = $this->exception->getCode();
        $this->json['message'] = $this->getExceptionMessage();
        $this->json['title'] = $title;
        $this->json['class'] = $class;
    }

    protected function processParams(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        if (count($this->exception->getParams()) === 0) {
            return;
        }

        foreach ($this->exception->getParams() as $key => $val) {
            $this->json['params'][$key] = static::toSafeString($val);
        }
    }

    protected function processSolutions(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (count($exception->getSolutions()) === 0) {
            return;
        }

        foreach ($exception->getSolutions() as $key => $val) {
            $this->json['solution'][$key] = $val;
        }
    }

    protected function processStackTrace(): void
    {
        $this->output .= '<span style="color:sandybrown">Stack Trace:</span>' . "\n";

        $this->processStackTraceInternal();
    }

    protected function processStackTraceInternal(): void
    {
        $in_atk = true;
        $trace = $this->getStackTrace(false);
        foreach ($trace as $index => $call) {
            $call = $this->parseStackTraceCall($call);

            $escape_frame = false;
            if ($in_atk && !preg_match('~atk4[/\\\\][^/\\\\]+[/\\\\]src[/\\\\]~', $call['file'])) {
                $escape_frame = true;
                $in_atk = false;
            }

            if ($escape_frame) {
                $call['args'] = array_map(function ($arg) {
                    return static::toSafeString($arg);
                }, $call['args']);
            }

            $this->json['stack'][] = $call;
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $previous = new static($this->exception->getPrevious(), $this->adapter);
        $text = (string) $previous; // need to trigger processAll;

        $this->json['previous'] = $previous->json;
    }

    protected function parseStackTraceCall(array $call): array
    {
        return [
            'line' => $call['line'] ?? '',
            'file' => $call['file'] ?? '',
            'class' => $call['class'] ?? null,
            'object' => ($call['object'] ?? null) !== null ? $this->toSafeString($call['object']) : null,
            'function' => $call['function'] ?? null,
            'args' => $call['args'] ?? [],
        ];
    }

    public function __toString(): string
    {
        try {
            $this->processAll();
        } catch (\Throwable $e) {
            // fallback if error occur
            $this->json = [
                'success' => false,
                'code' => $this->exception->getCode(),
                'message' => 'Error during json renderer: ' . $this->exception->getMessage(),
                // avoid translation
                // 'message' => $this->_($this->exception->getMessage()),
                'title' => get_class($this->exception),
                'class' => get_class($this->exception),
                'params' => [],
                'solution' => [],
                'trace' => [],
                'previous' => [
                    'title' => get_class($e),
                    'class' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }

        return (string) json_encode($this->json, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
    }
}
