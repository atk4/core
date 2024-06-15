<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Json extends RendererAbstract
{
    /** @var array<string, mixed> */
    protected array $json = [
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

    #[\Override]
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = get_class($this->exception);

        $this->json['code'] = $this->exception->getCode();
        $this->json['message'] = $this->getExceptionMessage();
        $this->json['title'] = $title;
        $this->json['class'] = $class;
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    protected function processStackTrace(): void
    {
        $this->output .= '<span style="color: sandybrown;">Stack Trace:</span>' . "\n";

        $this->processStackTraceInternal();
    }

    #[\Override]
    protected function processStackTraceInternal(): void
    {
        $inAtk = true;
        $trace = $this->getStackTrace(false);
        foreach ($trace as $index => $call) {
            $call = $this->parseStackTraceFrame($call);

            $escapeFrame = false;
            if ($inAtk && $call['file'] !== '' && !preg_match('~atk4[/\\\][^/\\\]+[/\\\]src[/\\\]~', $call['file'])) {
                $escapeFrame = true;
                $inAtk = false;
            }

            if ($escapeFrame) {
                $call['args'] = array_map(static function ($arg) {
                    return static::toSafeString($arg);
                }, $call['args']);
            }

            $this->json['stack'][] = $call;
        }
    }

    #[\Override]
    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $previous = new static($this->exception->getPrevious(), $this->adapter);
        $text = (string) $previous; // need to trigger processAll;

        $this->json['previous'] = $previous->json;
    }

    #[\Override]
    protected function parseStackTraceFrame(array $frame): array
    {
        return [
            'line' => $frame['line'] ?? '',
            'file' => $frame['file'] ?? '',
            'class' => $frame['class'] ?? null,
            'object' => ($frame['object'] ?? null) !== null ? static::toSafeString($frame['object']) : null,
            'function' => $frame['function'] ?? null,
            'args' => $frame['args'] ?? [],
        ];
    }

    #[\Override]
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
