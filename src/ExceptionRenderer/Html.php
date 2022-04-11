<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Html extends RendererAbstract
{
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = get_class($this->exception);

        $tokens = [
            '{TITLE}' => $title,
            '{CLASS}' => $class,
            '{MESSAGE}' => $this->getExceptionMessage(),
            '{CODE}' => $this->exception->getCode() ? ' [code: ' . $this->exception->getCode() . ']' : '',
        ];

        $this->output .= $this->replaceTokens($tokens, '
            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">{TITLE}</div>
                    {CLASS}{CODE}:
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

        if (count($this->exception->getParams()) === 0) {
            return;
        }

        $text = '
            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted red table">Exception Parameters</th></tr></thead>
                <tbody>{PARAMS}
                </tbody>
            </table>
        ';

        $tokens = [
            '{PARAMS}' => '',
        ];
        $textInner = '
                    <tr><td><b>{KEY}</b></td><td style="width: 100%;">{VAL}</td></tr>';
        foreach ($this->exception->getParams() as $key => $val) {
            $key = htmlentities($key);
            $val = '<span style="white-space: pre-wrap;">' . preg_replace('~(?<=\n)( +)~', '$1$1', htmlentities(static::toSafeString($val, true))) . '</span>';

            $tokens['{PARAMS}'] .= $this->replaceTokens(
                [
                    '{KEY}' => $key,
                    '{VAL}' => $val,
                ],
                $textInner
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

        if (count($exception->getSolutions()) === 0) {
            return;
        }

        $text = '
            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted green table">Suggested solutions</th></tr></thead>
                <tbody>{SOLUTIONS}
                </tbody>
            </table>
        ';

        $tokens = [
            '{SOLUTIONS}' => '',
        ];
        $textInner = '
                    <tr><td>{VAL}</td></tr>';
        foreach ($exception->getSolutions() as $key => $val) {
            $tokens['{SOLUTIONS}'] .= $this->replaceTokens(['{VAL}' => htmlentities($val)], $textInner);
        }

        $this->output .= $this->replaceTokens($tokens, $text);
    }

    protected function processStackTrace(): void
    {
        $this->output .= '
            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="4">Stack Trace</th></tr></thead>
                <thead><tr><th style="text-align: right">#</th><th>File</th><th>Object</th><th>Method</th></tr></thead>
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
                <td style="text-align: right">{INDEX}</td>
                <td>{FILE_LINE}</td>
                <td>{OBJECT}</td>
                <td>{FUNCTION}{FUNCTION_ARGS}</td>
            </tr>
        ';

        $inAtk = true;
        $shortTrace = $this->getStackTrace(true);
        $isShortened = end($shortTrace) && key($shortTrace) !== 0 && key($shortTrace) !== 'self';
        foreach ($shortTrace as $index => $call) {
            $call = $this->parseStackTraceCall($call);

            $escapeFrame = false;
            if ($inAtk && !preg_match('~atk4[/\\\\][^/\\\\]+[/\\\\]src[/\\\\]~', $call['file'])) {
                $escapeFrame = true;
                $inAtk = false;
            }

            $tokens = [];
            $tokens['{INDEX}'] = $index === 'self' ? '' : $index + 1;
            $tokens['{FILE_LINE}'] = $call['file_rel'] !== '' ? $call['file_rel'] . ':' . $call['line'] : '';
            $tokens['{OBJECT}'] = $call['object'] !== false ? $call['object_formatted'] : '-';
            $tokens['{CLASS}'] = $call['class'] !== false ? $call['class_formatted'] . '::' : '';
            $tokens['{CSS_CLASS}'] = $escapeFrame ? 'negative' : '';

            $tokens['{FUNCTION}'] = $call['function'];

            if ($index === 'self') {
                $tokens['{FUNCTION_ARGS}'] = '';
            } elseif (count($call['args']) === 0) {
                $tokens['{FUNCTION_ARGS}'] = '()';
            } else {
                if ($escapeFrame) {
                    $tokens['{FUNCTION_ARGS}'] = '(<br />' . implode(',' . '<br />', array_map(function ($arg) {
                        return htmlentities(static::toSafeString($arg, false, 1));
                    }, $call['args'])) . ')';
                } else {
                    $tokens['{FUNCTION_ARGS}'] = '(...)';
                }
            }

            $this->output .= $this->replaceTokens($tokens, $text);
        }

        if ($isShortened) {
            $this->output .= '
                <tr>
                    <td style="text-align: right">...</td>
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
