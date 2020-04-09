<?php

declare(strict_types=1);

namespace atk4\core\ExceptionRenderer;

use atk4\core\Exception;

class HTML extends RendererAbstract
{
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = $this->getExceptionName();

        $tokens = [
            '{TITLE}'   => $title,
            '{CLASS}'   => $class,
            '{MESSAGE}' => $this->_($this->exception->getMessage()),
            '{CODE}'    => $this->exception->getCode() ? ' [code: '.$this->exception->getCode().']' : '',
        ];

        $this->output .= $this->replaceTokens($tokens, '
            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">{TITLE}</div>
                    {CLASS} {CODE}:
                    {MESSAGE}
                </div>
            </div>
        ');
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

        $text = '
            <div class="ui stacked segments">
                <div class="ui inverted red segment fitted">Exception Parameters</div>
                {PARAMS}
            </div>
        ';

        $tokens = [
            '{PARAMS}' => '',
        ];
        $text_inner = '<div class="ui segment"><b>{KEY}</b>:{VAL}</div>';
        foreach ($exception->getParams() as $key => $val) {
            $key = str_pad((string) $key, 19, ' ', STR_PAD_LEFT);
            $key = htmlentities($key);
            $val = htmlentities(static::toSafeString($val));

            $tokens['{PARAMS}'] .= $this->replaceTokens(
                [
                    '{KEY}' => $key,
                    '{VAL}' => $val,
                ],
                $text_inner
            );
        }

        $this->output .= $this->replaceTokens($tokens, $text);
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

        $text = '
            <div class="ui stacked segments">
                <div class="ui inverted secondary green segment small">Suggested solutions</div>
                {SOLUTIONS}
            </div>
        ';

        $tokens = [
            '{SOLUTIONS}' => '',
        ];
        $text_inner = '<div class="ui segment">{VAL}</div>';
        foreach ($exception->getSolutions() as $key => $val) {
            $tokens['{SOLUTIONS}'] .= $this->replaceTokens(['{VAL}' => htmlentities($val)], $text_inner);
        }

        $this->output .= $this->replaceTokens($tokens, $text);
    }

    protected function processStackTrace(): void
    {
        $this->output .= '
            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="4">Stack Trace</th></tr></thead>
                <thead><tr><th style="text-align:right">#</th><th>File</th><th>Object</th><th>Method</th></tr></thead>
                <tbody>
        ';

        $this->processStackTraceInternal();

        $this->output .= '
                </tbody>
            </table>
        ';
    }

    protected function processStackTraceInternal(): void
    {
        $text = '
            <tr class="{CSS_CLASS}">
                <td style="text-align:right">{INDEX}</td>
                <td>{FILE_LINE}</td>
                <td>{OBJECT}</td>
                <td>{FUNCTION}{FUNCTION_ARGS}</td>
            </tr>
        ';

        $in_atk = true;
        $escape_frame = false;
        $short_trace = $this->getStackTrace(true);
        $is_shortened = end($short_trace) && key($short_trace) !== 0;
        foreach ($short_trace as $index => $call) {
            $call = $this->parseCallTraceObject($call);

            if ($in_atk && !preg_match('/atk4\/.*\/src\//', $call['file'])) {
                $escape_frame = true;
                $in_atk = false;
            }

            $tokens = [];
            $tokens['{INDEX}'] = $index + 1;
            $tokens['{FILE_LINE}'] = empty(trim($call['file_formatted'])) ? '' : $call['file_formatted'].':'.$call['line_formatted'];
            $tokens['{OBJECT}'] = false !== $call['object'] ? $call['object_formatted'] : '-';
            $tokens['{CLASS}'] = false !== $call['class'] ? $call['class'].'::' : '';
            $tokens['{CSS_CLASS}'] = $escape_frame ? 'negative' : '';

            $tokens['{FUNCTION}'] = $call['function'];
            $tokens['{FUNCTION_ARGS}'] = '()';

            if ($escape_frame) {
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = static::toSafeString($arg);
                }

                if (!empty($args)) {
                    $tokens['{FUNCTION_ARGS}'] = '(<br />'.implode(','.'<br />', $args).')';
                }
            }

            $this->output .= $this->replaceTokens($tokens, $text);
        }

        if ($is_shortened) {
            $this->output .= '
                <tr class="">
                    <td style="text-align:right">...</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            ';
        }
    }

    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= '
            <div class="ui top attached segment">
                <div class="ui top attached label">Caused by Previous Exception:</div>
            </div>
        ';

        $this->output .= (string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception));
    }
}
