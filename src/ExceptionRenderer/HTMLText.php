<?php

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

class HTMLText extends RendererAbstract
{
    protected function processHeader(): void
    {
        $text = <<<'HTML'
--[ {TITLE} ]---------------------------
{CLASS}: <span color='pink'><b>{MESSAGE}</b></span> {CODE}

HTML;

        $title = $this->is_atk_exception
            ? $this->exception->getCustomExceptionTitle()
            : static::getClassShortName($this->exception).' Error';

        $class = $this->is_atk_exception
            ? $this->exception->getCustomExceptionName()
            : get_class($this->exception);

        $tokens = [
            '{TITLE}'   => $title,
            '{CLASS}'   => $class,
            '{MESSAGE}' => $this->exception->getMessage(),
            '{CODE}'    => $this->exception->getCode() ? ' [code: '.$this->exception->getCode().']' : '',
        ];

        $this->output .= $this->replaceTokens($tokens, $text);
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

        $this->output .= PHP_EOL.'<span style="color:cyan;">Exception params: </span>';

        foreach ($exception->getParams() as $key => $val) {
            $key = str_pad($key, 19, ' ', STR_PAD_LEFT);
            $key = htmlentities($key);
            $val = htmlentities(static::toSafeString($val));

            $this->output .= PHP_EOL.' - '.$key.': '.$val;
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

        $this->output .= PHP_EOL.PHP_EOL.'<span style="color:lightgreen">Suggested solutions</span>:';
        foreach ($exception->getSolutions() as $key => $val) {
            $this->output .= PHP_EOL.' - '.htmlentities($val);
        }
    }

    protected function processStackTrace(): void
    {
        $this->output .= <<<'HTML'
<span style="color:sandybrown">Stack Trace:</span>

HTML;

        $this->processStackTraceInternal();
    }

    protected function processStackTraceInternal(): void
    {
        $text = <<<'HTML'
<span color='cyan'>{FILE}</span>:<span color='pink'>{LINE}</span>{OBJECT} <span color='gray'>{CLASS}<span style="color:{FUNCTION_COLOR}">{FUNCTION}<span style='color:pink'>{FUNCTION_ARGS}</span></span>

HTML;

        $in_atk = true;
        $escape_frame = false;
        $tokens_trace = [];
        $trace = $this->is_atk_exception ? $this->exception->getMyTrace() : $this->exception->getTrace();
        $trace_count = count($trace);
        foreach ($trace as $index => $call) {
            $call = $this->parseCallTraceObject($call);

            if ($in_atk && !preg_match('/atk4\/.*\/src\//', $call['file'])) {
                $escape_frame = true;
                $in_atk = false;
            }

            $tokens_trace['{FILE}'] = $call['file_formatted'];
            $tokens_trace['{LINE}'] = $call['line_formatted'];
            $tokens_trace['{OBJECT}'] = $call['object'] !== null ? " - <span style='color:yellow'>".$call['object_formatted'].'</span>' : '';
            $tokens_trace['{CLASS}'] = $call['class'] !== null ? $call['class'].'::' : '';

            $tokens_trace['{FUNCTION_COLOR}'] = $escape_frame ? 'pink' : 'gray';
            $tokens_trace['{FUNCTION}'] = $call['function'];
            $tokens_trace['{FUNCTION_ARGS}'] = '()';

            if ($escape_frame) {
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = static::toSafeString($arg);
                }

                $tokens_trace['{FUNCTION_ARGS}'] = PHP_EOL.str_repeat(' ', 40).'('.implode(', ', $args).')';
            }

            $this->output .= $this->replaceTokens($tokens_trace, $text);
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= PHP_EOL.'Caused by Previous Exception:'.PHP_EOL;

        $this->output .= (string) (new static($this->exception->getPrevious()));
    }
}
