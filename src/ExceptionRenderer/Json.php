<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Json extends RendererAbstract
{
    /** @var array<string, mixed> */
    protected array $json = [
        'message' => '',
        'title' => '',
        'class' => '',
        'code' => 0,
        'params' => [],
        'solution' => [],
        'trace' => [],
        'previous' => null,
    ];

    #[\Override]
    protected function processHeader(): void
    {
        $this->json['message'] = $this->getExceptionMessage();
        $this->json['title'] = $this->getExceptionTitle();
        $this->json['class'] = $this->formatClass(get_class($this->exception));
        $this->json['code'] = $this->exception->getCode();
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

            $this->json['trace'][] = $call;
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
        return [ // @phpstan-ignore return.type
            'file' => $frame['file'] ?? '',
            'line' => $frame['line'] ?? '',
            'class' => $frame['class'] ?? null,
            'object' => ($frame['object'] ?? null) !== null ? static::toSafeString($frame['object']) : null,
            'function' => $frame['function'] ?? null,
            'args' => $frame['args'] ?? [],
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        $toStringFx = fn () => json_encode($this->json, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);

        try {
            $this->processAll();

            return $toStringFx();
        } catch (\Throwable $e) {
            // fallback if error occur
            $this->json = [
                'message' => 'ATK4 CORE ERROR - EXCEPTION JSON RENDER FAILED: ' . $this->exception->getMessage(),
                // avoid translation
                // 'message' => $this->_($this->exception->getMessage()),
                'title' => get_class($this->exception),
                'class' => get_class($this->exception),
                'code' => $this->exception->getCode(),
                'params' => [],
                'solution' => [],
                'trace' => [],
                'previous' => [
                    'message' => $e->getMessage(),
                    'title' => get_class($e),
                    'class' => get_class($e),
                    'code' => $e->getCode(),
                ],
            ];
        }

        return $toStringFx();
    }
}
