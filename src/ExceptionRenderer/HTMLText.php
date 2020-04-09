<?php

declare(strict_types=1);

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

class HTMLText extends RendererAbstract
{
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = get_class($this->exception);

        $tokens = [
            '{TITLE}'   => $title,
            '{CLASS}'   => $class,
            '{MESSAGE}' => $this->_($this->exception->getMessage()),
            '{CODE}'    => $this->exception->getCode() ? ' [code: '.$this->exception->getCode().']' : '',
        ];

        $this->output .= $this->replaceTokens(
            $tokens,
            <<<'HTML'
--[ {TITLE} ]---------------------------
{CLASS}: <span color='pink'><b>{MESSAGE}</b></span> {CODE}

HTML
        );
    }

    protected function processParams(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (0 === count($exception->getParams())) {
            return;
        }

        $this->output .= PHP_EOL.'<span style="color:cyan;">Exception params: </span>';

        foreach ($exception->getParams() as $key => $val) {
            $key = str_pad((string) $key, 19, ' ', STR_PAD_LEFT);
            $key = htmlentities($key);
            $val = htmlentities(static::toSafeString($val));

            $this->output .= PHP_EOL.' - '.$key.': '.$val;
        }
    }

    protected function processSolutions(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (0 === count($exception->getSolutions())) {
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
<span color='cyan'>{FILE}</span>:<span color='pink'>{LINE}</span>{OBJECT} <span color='gray'>{CLASS}<span style="color:{FUNCTION_COLOR}">{FUNCTION}<span style='color:pink'>{FUNCTION_ARGS}</span></span></span>

HTML;

        $in_atk = true;
        $escape_frame = false;
        $short_trace = $this->getStackTrace(true);
        $is_shortened = end($short_trace) && key($short_trace) !== 0;
        foreach ($short_trace as $index => $call) {
            $call = $this->parseStackTraceCall($call);

            if ($in_atk && !preg_match('/atk4\/.*\/src\//', $call['file'])) {
                $escape_frame = true;
                $in_atk = false;
            }

            $tokens = [];
            $tokens['{FILE}'] = $call['file_formatted'];
            $tokens['{LINE}'] = $call['line_formatted'];
            $tokens['{OBJECT}'] = null !== $call['object'] ? " - <span style='color:yellow'>".$call['object_formatted'].'</span>' : '';
            $tokens['{CLASS}'] = null !== $call['class'] ? $call['class'].'::' : '';

            $tokens['{FUNCTION_COLOR}'] = $escape_frame ? 'pink' : 'gray';
            $tokens['{FUNCTION}'] = $call['function'];
            $tokens['{FUNCTION_ARGS}'] = '()';

            if ($escape_frame) {
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = static::toSafeString($arg);
                }

                $tokens['{FUNCTION_ARGS}'] = '('.PHP_EOL.str_repeat(' ', 40).implode(','.PHP_EOL.str_repeat(' ', 40), $args).')';
            }

            $this->output .= $this->replaceTokens($tokens, $text);
        }

        if ($is_shortened) {
            $this->output .= '...
            ';
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= PHP_EOL.'Caused by Previous Exception:'.PHP_EOL;

        $this->output .= (string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception));
    }
}
