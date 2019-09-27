<?php

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

class JSON extends RendererAbstract
{
    protected $json = [
        'success'  => false,
        'code'     => 0,
        'message'  => '',
        'title'    => '',
        'class'    => '',
        'params'   => [],
        'solution' => [],
        'trace'    => [],
        'previous' => [],
    ];

    protected function processHeader(): void
    {
        $title = $this->is_atk_exception
            ? $this->exception->getCustomExceptionTitle()
            : static::getClassShortName($this->exception) . ' Error';

        $class = $this->is_atk_exception
            ? $this->exception->getCustomExceptionName()
            : get_class($this->exception);

        $this->json['code']    = $this->exception->getCode();
        $this->json['message'] = $this->exception->getMessage();
        $this->json['title']   = $title;
        $this->json['class']   = $class;
    }

    protected function processParams(): void
    {
        if (false === $this->is_atk_exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (count($exception->getParams()) === 0) {
            return;
        }

        foreach ($exception->getParams() as $key => $val) {
            $this->json['params'][$key] = static::toSafeString($val);
        }
    }

    protected function processSolutions(): void
    {
        if (false === $this->is_atk_exception) {
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
        $this->output .= <<<HTML
<span style="color:sandybrown">Stack Trace:</span>

HTML;

        $this->processStackTraceInternal();
    }

    protected function processStackTraceInternal(): void
    {
        $in_atk       = true;
        $escape_frame = false;
        $tokens_trace = [];
        $trace        = $this->is_atk_exception ? $this->exception->getMyTrace() : $this->exception->getTrace();
        $trace_count  = count($trace);
        foreach ($trace as $index => $call) {
            $call = $this->parseCallTraceObject($call);

            if ($in_atk && !preg_match('/atk4\/.*\/src\//', $call['file'])) {
                $escape_frame = true;
                $in_atk       = false;
            }

            if ($escape_frame) {
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = static::toSafeString($arg);
                }

                $call['args'] = $args;
            }

            $this->json['stack'][] = $call;
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $previous = new static($this->exception->getPrevious());
        $text     = (string)$previous; // need to trigger processAll;

        $this->json['previous'] = $previous->json;
    }

    protected function parseCallTraceObject($call): array
    {
        return [
            'line'     => $call['line'] ?? '',
            'file'     => $call['file'] ?? '',
            'class'    => $call['class'] ?? null,
            'object'   => ($call['object'] ?? null) !== null ? ($call['object']->name ?? get_class($call['object'])) : null,
            'function' => $call['function'] ?? null,
            'args'     => $call['args'] ?? [],
        ];
    }

    public function __toString(): string
    {
        $this->processAll();
        return (string)json_encode($this->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}