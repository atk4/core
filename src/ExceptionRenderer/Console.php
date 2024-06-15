<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Console extends RendererAbstract
{
    #[\Override]
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

        $this->output .= $this->replaceTokens(<<<'EOF'
            \e[1;41m--[ {TITLE} ]\e[0m
            {CLASS}: \e[1;30m{MESSAGE}\e[0;31m {CODE}
            EOF, $tokens);
    }

    #[\Override]
    protected function processParams(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        /** @var Exception $exception */
        $exception = $this->exception;

        if (count($exception->getParams()) === 0) {
            return;
        }

        foreach ($exception->getParams() as $key => $val) {
            $key = str_pad($key, 19, ' ', \STR_PAD_LEFT);
            $this->output .= \PHP_EOL . "\e[91m" . $key . ': ' . static::toSafeString($val) . "\e[0m";
        }
    }

    #[\Override]
    protected function processSolutions(): void
    {
        if (!$this->exception instanceof Exception) {
            return;
        }

        if (count($this->exception->getSolutions()) === 0) {
            return;
        }

        foreach ($this->exception->getSolutions() as $key => $val) {
            $this->output .= \PHP_EOL . "\e[92mSolution: " . $val . "\e[0m";
        }
    }

    #[\Override]
    protected function processStackTrace(): void
    {
        $this->output .= <<<'EOF'

            \e[1;41m--[ Stack Trace ]\e[0m

            EOF;

        $this->processStackTraceInternal();
    }

    #[\Override]
    protected function processStackTraceInternal(): void
    {
        $text = <<<'EOF'
            \e[0m{FILE}\e[0m:\e[0;31m{LINE}\e[0m {OBJECT} {CLASS}{FUNCTION_COLOR}{FUNCTION}{FUNCTION_ARGS}

            EOF;

        $inAtk = true;
        $shortTrace = $this->getStackTrace(true);
        $isShortened = end($shortTrace) && key($shortTrace) !== 0 && key($shortTrace) !== 'self';
        foreach ($shortTrace as $index => $call) {
            $call = $this->parseStackTraceFrame($call);

            $escapeFrame = false;
            if ($inAtk && $call['file'] !== '' && !preg_match('~atk4[/\\\][^/\\\]+[/\\\]src[/\\\]~', $call['file'])) {
                $escapeFrame = true;
                $inAtk = false;
            }

            $tokens = [];
            $tokens['{FILE}'] = str_pad(mb_substr($call['file_rel'], -40), 40, ' ', \STR_PAD_LEFT);
            $tokens['{LINE}'] = str_pad($call['line'], 4, ' ', \STR_PAD_LEFT);
            $tokens['{OBJECT}'] = $call['object'] !== null ? " - \e[0;32m" . $call['object_formatted'] . "\e[0m" : '';
            $tokens['{CLASS}'] = $call['class'] !== null ? "\e[0;32m" . $call['class_formatted'] . "::\e[0m" : '';

            $tokens['{FUNCTION_COLOR}'] = $escapeFrame ? "\e[0;31m" : "\e[0;33m";
            $tokens['{FUNCTION}'] = $call['function'];

            if ($index === 'self') {
                $tokens['{FUNCTION_ARGS}'] = '';
            } elseif (count($call['args']) === 0) {
                $tokens['{FUNCTION_ARGS}'] = '()';
            } else {
                if ($escapeFrame) {
                    $tokens['{FUNCTION_ARGS}'] = "\e[0;31m(" . \PHP_EOL . str_repeat(' ', 40) . implode(',' . \PHP_EOL . str_repeat(' ', 40), array_map(static function ($arg) {
                        return static::toSafeString($arg);
                    }, $call['args'])) . ')';
                } else {
                    $tokens['{FUNCTION_ARGS}'] = '(...)';
                }
            }

            $this->output .= $this->replaceTokens($text, $tokens);
        }

        if ($isShortened) {
            $this->output .= '...
            ';
        }
    }

    #[\Override]
    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= \PHP_EOL . "\e[1;45mCaused by Previous Exception:\e[0m" . \PHP_EOL;

        $this->output .= (string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception));
        $this->output .= <<<'EOF'
            \e[1;31m--
            \e[0m
            EOF;
    }
}
