<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Html extends RendererAbstract
{
    protected function encodeHtml(string $value): string
    {
        return htmlspecialchars($value, \ENT_HTML5 | \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    #[\Override]
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = $this->formatClass(get_class($this->exception));

        $tokens = [
            '{TITLE}' => $this->encodeHtml($title),
            '{CLASS}' => $this->encodeHtml($class),
            '{MESSAGE}' => $this->encodeHtml($this->getExceptionMessage()),
            '{CODE}' => $this->exception->getCode() ? ' [code: ' . $this->exception->getCode() . ']' : '',
        ];

        $this->output .= $this->replaceTokens(<<<'EOF'
            <div class="ui negative icon message">
                <i class="warning sign icon"></i>
                <div class="content">
                    <div class="header">{TITLE}</div>
                    {CLASS}{CODE}:
                    {MESSAGE}
                </div>
            </div>

            EOF, $tokens);
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

        $text = <<<'EOF'

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted red table">Exception Parameters</th></tr></thead>
                <tbody>{PARAMS}
                </tbody>
            </table>

            EOF;

        $tokens = [
            '{PARAMS}' => '',
        ];
        $textInner = <<<'EOF'

                    <tr><td><b>{KEY}</b></td><td style="width: 100%;">{VAL}</td></tr>
            EOF;
        foreach ($this->exception->getParams() as $key => $val) {
            $valHtml = '<span style="white-space: pre-wrap;">' . preg_replace('~(?<=\n)( +)~', '$1$1', $this->encodeHtml(static::toSafeString($val, true))) . '</span>';

            $tokens['{PARAMS}'] .= $this->replaceTokens($textInner, [
                '{KEY}' => $this->encodeHtml($key),
                '{VAL}' => $valHtml,
            ]);
        }

        $this->output .= $this->replaceTokens($text, $tokens);
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

        $text = <<<'EOF'

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="2" class="ui inverted green table">Suggested solutions</th></tr></thead>
                <tbody>{SOLUTIONS}
                </tbody>
            </table>

            EOF;

        $tokens = [
            '{SOLUTIONS}' => '',
        ];
        $textInner = <<<'EOF'

                    <tr><td>{VAL}</td></tr>
            EOF;
        foreach ($exception->getSolutions() as $key => $val) {
            $tokens['{SOLUTIONS}'] .= $this->replaceTokens($textInner, ['{VAL}' => $this->encodeHtml($val)]);
        }

        $this->output .= $this->replaceTokens($text, $tokens);
    }

    #[\Override]
    protected function processStackTrace(): void
    {
        $this->output .= <<<'EOF'

            <table class="ui very compact small selectable table top aligned">
                <thead><tr><th colspan="4">Stack Trace</th></tr></thead>
                <thead><tr><th style="text-align: right">#</th><th>File</th><th>Object</th><th>Method</th></tr></thead>
                <tbody>

            EOF;

        $this->processStackTraceInternal();

        $this->output .= <<<'EOF'

                </tbody>
            </table>

            EOF;
    }

    #[\Override]
    protected function processStackTraceInternal(): void
    {
        $text = <<<'EOF'

                    <tr class="{CSS_CLASS}">
                        <td style="text-align: right">{INDEX}</td>
                        <td>{FILE_LINE}</td>
                        <td>{OBJECT}</td>
                        <td>{FUNCTION}{FUNCTION_ARGS}</td>
                    </tr>

            EOF;

        $inAtk = true;
        $shortTrace = $this->getStackTrace(true);
        $isShortened = array_key_last($shortTrace) > 0 && array_key_last($shortTrace) !== 'self';
        foreach ($shortTrace as $index => $call) {
            $call = $this->parseStackTraceFrame($call);

            $escapeFrame = false;
            if ($inAtk && $call['file'] !== '' && !preg_match('~atk4[/\\\][^/\\\]+[/\\\]src[/\\\]~', $call['file'])) {
                $escapeFrame = true;
                $inAtk = false;
            }

            $tokens = [];
            $tokens['{INDEX}'] = $index === 'self' ? '' : $index + 1;
            $tokens['{FILE_LINE}'] = $call['file_rel'] !== '' ? $this->encodeHtml($call['file_rel']) . ':' . $call['line'] : '';
            $tokens['{OBJECT}'] = $call['object'] !== null ? $this->encodeHtml($call['object_formatted']) : '-';
            $tokens['{CLASS}'] = $call['class'] !== null ? $this->encodeHtml($call['class_formatted']) . '::' : '';
            $tokens['{CSS_CLASS}'] = $escapeFrame ? 'negative' : '';

            $tokens['{FUNCTION}'] = $call['function'];

            if ($index === 'self') {
                $tokens['{FUNCTION_ARGS}'] = '';
            } elseif (count($call['args']) === 0) {
                $tokens['{FUNCTION_ARGS}'] = '()';
            } else {
                if ($escapeFrame) {
                    $tokens['{FUNCTION_ARGS}'] = '(<br>' . implode(',<br>', array_map(function ($arg) {
                        return $this->encodeHtml(static::toSafeString($arg, false, 1));
                    }, $call['args'])) . ')';
                } else {
                    $tokens['{FUNCTION_ARGS}'] = '(...)';
                }
            }

            $this->output .= $this->replaceTokens($text, $tokens);
        }

        if ($isShortened) {
            $this->output .= <<<'EOF'

                        <tr>
                            <td style="text-align: right">...</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                EOF;
        }
    }

    #[\Override]
    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= <<<'EOF'

            <div class="ui top attached segment">
                <div class="ui top attached label">Caused by Previous Exception:</div>
            </div>


            EOF;

        $this->output .= (string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception));
    }
}
