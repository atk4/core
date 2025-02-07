<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Console extends RendererAbstract
{
    private const RESET = "\e[0m";
    private const BOLD = "\e[1m";
    private const COLOR_BLACK = "\e[30m";
    private const COLOR_RED = "\e[31m";
    private const COLOR_GREEN = "\e[32m";
    private const COLOR_YELLOW = "\e[33m";
    private const BG_COLOR_RED = "\e[41m";
    private const BG_COLOR_MAGENTA = "\e[45m";
    private const COLOR_BRIGHT_RED = "\e[91m";
    private const COLOR_BRIGHT_GREEN = "\e[92m";

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

        $this->output .= $this->replaceTokens(
            "\n" .
            self::text('--[ {TITLE} ]', [self::BOLD, self::BG_COLOR_RED]) . "\n" .
            self::text('{CLASS}: ') .
                self::text('{MESSAGE}', [self::BOLD, self::COLOR_BLACK]) .
                self::text(' {CODE}', [self::COLOR_RED]) . "\n",
            $tokens
        );
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
            $this->output .= "\n" . self::text($key . ': ' . static::toSafeString($val), [self::COLOR_BRIGHT_RED]);
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
            $this->output .= "\n" . self::text('Solution: ' . $val, [self::COLOR_BRIGHT_GREEN]);
        }
    }

    #[\Override]
    protected function processStackTrace(): void
    {
        $this->output .= "\n" . self::text('--[ Stack Trace ]', [self::BOLD, self::BG_COLOR_RED]) . "\n";
        $this->processStackTraceInternal();
    }

    #[\Override]
    protected function processStackTraceInternal(): void
    {
        $text = self::text('{FILE}:', [self::RESET]) .
                self::text('{LINE}', [self::COLOR_RED]) . ' ' .
                self::text('{OBJECT} {CLASS}{FUNCTION}{FUNCTION_ARGS}', [self::RESET]) .
                "\n";

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

            $tokens = [
                '{FILE}' => str_pad(mb_substr($call['file_rel'], -40), 40, ' ', \STR_PAD_LEFT),
                '{LINE}' => str_pad($call['line'], 4, ' ', \STR_PAD_LEFT),
                '{OBJECT}' => $call['object'] !== null ? ' - ' . self::text($call['object_formatted'], [self::COLOR_GREEN]) : '',
                '{CLASS}' => $call['class'] !== null ? self::text($call['class_formatted'] . '::', [self::COLOR_GREEN]) : '',
                '{FUNCTION}' => $call['class'] !== null ? self::text($call['function'], [$escapeFrame ? self::COLOR_RED : self::COLOR_YELLOW]) : '',
            ];

            if ($index === 'self') {
                $tokens['{FUNCTION_ARGS}'] = '';
            } elseif (count($call['args']) === 0) {
                $tokens['{FUNCTION_ARGS}'] = '()';
            } else {
                if ($escapeFrame) {
                    $tokens['{FUNCTION_ARGS}'] = self::text('(' . "\n" . str_repeat(' ', 40) . implode(',' . "\n" . str_repeat(' ', 40), array_map(static function ($arg) {
                        return static::toSafeString($arg);
                    }, $call['args'])) . ')', [self::COLOR_RED]);
                } else {
                    $tokens['{FUNCTION_ARGS}'] = '(...)';
                }
            }

            $this->output .= $this->replaceTokens($text, $tokens);
        }

        if ($isShortened) {
            $this->output .= '...' . "\n";
        }
    }

    #[\Override]
    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= "\n" .
            self::text('Caused by Previous Exception:', [self::BOLD, self::BG_COLOR_MAGENTA]) . "\n" .
            self::text((string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception))) .
            self::text('--', [self::BOLD, self::COLOR_RED]);
    }

    /**
     * Style text with ASCII colors.
     *
     * @param array<int,string> $styles
     */
    private static function text(string $text, array $styles = []): string
    {
        return implode('', $styles) . $text . (count($styles) === 0 ? '' : self::RESET);
    }
}
