<?php

declare(strict_types=1);

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

class Console extends RendererAbstract
{
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = $this->getExceptionName();

        $tokens = [
            '{TITLE}'   => $title,
            '{CLASS}'   => $class,
            '{MESSAGE}' => $this->exception->getMessage(),
            '{CODE}'    => $this->exception->getCode() ? ' [code: '.$this->exception->getCode().']' : '',
        ];

        $this->output .= $this->replaceTokens(
            $tokens,
            <<<TEXT
\e[1;41m--[ {TITLE} ]\e[0m
{CLASS}: \e[1;30m{MESSAGE}\e[0;31m {CODE}
TEXT
);
    }

    protected function processParams(): void
    {
        if (false === $this->is_atk_exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (0 === count($exception->getParams())) {
            return;
        }

        foreach ($exception->getParams() as $key => $val) {
            $key = str_pad((string) $key, 19, ' ', STR_PAD_LEFT);
            $this->output .= PHP_EOL."\e[91m".$key.': '.static::toSafeString($val)."\e[0m";
        }
    }

    protected function processSolutions(): void
    {
        if (false === $this->is_atk_exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (0 === count($exception->getSolutions())) {
            return;
        }

        foreach ($exception->getSolutions() as $key => $val) {
            $this->output .= PHP_EOL."\e[92mSolution: ".$val."\e[0m";
        }
    }

    protected function processStackTrace(): void
    {
        $this->output .= <<<TEXT

\e[1;41m--[ Stack Trace ]\e[0m

TEXT;

        $this->processStackTraceInternal();
    }

    protected function processStackTraceInternal(): void
    {
        $text = <<<TEXT
\e[0m{FILE}\e[0m:\e[0;31m{LINE}\e[0m {OBJECT} {CLASS}{FUNCTION_COLOR}{FUNCTION}{FUNCTION_ARGS}

TEXT;

        $in_atk = true;
        $escape_frame = false;
        $tokens = [];
        $trace = $this->is_atk_exception ? $this->exception->getMyTrace() : $this->exception->getTrace();
        $trace_count = count($trace);
        foreach ($trace as $index => $call) {
            $call = $this->parseCallTraceObject($call);

            if ($in_atk && !preg_match('/atk4\/.*\/src\//', $call['file'])) {
                $escape_frame = true;
                $in_atk = false;
            }

            $tokens['{FILE}'] = $call['file_formatted'];
            $tokens['{LINE}'] = $call['line_formatted'];
            $tokens['{OBJECT}'] = null !== $call['object_formatted'] ? " - \e[0;32m".$call['object_formatted']."\e[0m" : '';
            $tokens['{CLASS}'] = null !== $call['class'] ? "\e[0;32m".$call['class']."::\e[0m" : '';

            $tokens['{FUNCTION_COLOR}'] = $escape_frame ? "\e[0;31m" : "\e[0;33m";
            $tokens['{FUNCTION}'] = $call['function'];
            $tokens['{FUNCTION_ARGS}'] = '()';

            if ($escape_frame) {
                $escape_frame = false;
                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = static::toSafeString($arg);
                }

                $tokens['{FUNCTION_ARGS}'] = PHP_EOL.str_repeat(' ', 40)."\e[0;31m(".implode(', ', $args).')';
            }

            $this->output .= $this->replaceTokens($tokens, $text);
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= PHP_EOL."\e[1;45mCaused by Previous Exception:\e[0m".PHP_EOL;

        $this->output .= (string) (new static($this->exception->getPrevious()));
        $this->output .= <<<TEXT
\e[1;31m--
\e[0m
TEXT;
    }
}
