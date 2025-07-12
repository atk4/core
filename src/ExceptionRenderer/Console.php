<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;

class Console extends RendererAbstract
{
    private const RESET = "\e[0m";
    private const FORMAT_BOLD = "\e[1m";
    private const COLOR_BLACK = "\e[30m";
    private const COLOR_RED = "\e[31m";
    private const COLOR_GREEN = "\e[32m";
    private const COLOR_YELLOW = "\e[33m";
    private const BACKGROUND_COLOR_RED = "\e[41m";
    private const BACKGROUND_COLOR_MAGENTA = "\e[45m";
    private const COLOR_BRIGHT_RED = "\e[91m";
    private const COLOR_BRIGHT_GREEN = "\e[92m";

    /**
     * @param list<self::FORMAT_*|self::COLOR_*|self::BACKGROUND_COLOR_*> $formats
     */
    private function text(string $text, array $formats = []): string
    {
        $text = str_replace("\e", '', $text);

        return $formats === []
            ? $text
            : implode('', $formats) . $text . self::RESET;
    }

    private function optimizeText(string $value): string
    {
        $res = preg_replace("~\e\\[\\d{1,2}m\e\\[0m~", '', $value);
        $res = preg_replace("~(?<=\e\\[\\d|\e\\[\\d{2})m\e\\[(\\d{1,2})(?=m)~", ';$1', $res);

        return implode("\n", array_map(static fn ($v) => rtrim($v, ' '), explode("\n", $res)));
    }

    #[\Override]
    protected function processAll(): void
    {
        parent::processAll();

        $this->output = $this->optimizeText(self::RESET . $this->output);
    }

    #[\Override]
    protected function processHeader(): void
    {
        $title = $this->getExceptionTitle();
        $class = $this->formatClass(get_class($this->exception));

        $this->output .= $this->text('--[ ' . $title . ' ]', [self::FORMAT_BOLD, self::BACKGROUND_COLOR_RED]) . "\n"
            . $this->text($class . ': ')
            . $this->text($this->getExceptionMessage(), [self::FORMAT_BOLD, self::COLOR_BLACK])
            . ($this->exception->getCode() !== 0 ? ' ' . $this->text('[code: ' . $this->exception->getCode() . ']', [self::COLOR_RED]) : '');
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
            $this->output .= "\n" . $this->text($key . ': ' . static::toSafeString($val), [self::COLOR_BRIGHT_RED]);
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
            $this->output .= "\n" . $this->text('Solution: ' . $val, [self::COLOR_BRIGHT_GREEN]);
        }
    }

    #[\Override]
    protected function processStackTrace(): void
    {
        $this->output .= "\n" . $this->text('--[ Stack Trace ]', [self::FORMAT_BOLD, self::BACKGROUND_COLOR_RED]) . "\n";
        $this->processStackTraceInternal();
    }

    #[\Override]
    protected function processStackTraceInternal(): void
    {
        $text = '{FILE}:'
            . $this->text('{LINE}', [self::COLOR_RED]) . ' '
            . '{OBJECT} {CLASS}{FUNCTION}{FUNCTION_ARGS}';

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

            $functionColor = $escapeFrame ? self::COLOR_RED : self::COLOR_YELLOW;

            $tokens = [
                '{FILE}' => $this->text(str_pad(mb_substr($call['file_rel'], -40), 40, ' ', \STR_PAD_LEFT)),
                '{LINE}' => $this->text(str_pad($call['line'], 4, ' ', \STR_PAD_LEFT)),
                '{OBJECT}' => $call['object'] !== null ? ' - ' . $this->text($call['object_formatted'], [self::COLOR_GREEN]) : '',
                '{CLASS}' => $call['class'] !== null ? $this->text($call['class_formatted'] . '::', [self::COLOR_GREEN]) : '',
                '{FUNCTION}' => $call['function'] !== null ? $this->text($call['function'], [$functionColor]) : '',
            ];

            if ($index === 'self') {
                $tokens['{FUNCTION_ARGS}'] = '';
            } elseif (count($call['args']) === 0) {
                $tokens['{FUNCTION_ARGS}'] = $this->text('()', [$functionColor]);
            } else {
                if ($escapeFrame) {
                    $tokens['{FUNCTION_ARGS}'] = $this->text('(' . "\n" . str_repeat(' ', 40) . implode(',' . "\n" . str_repeat(' ', 40), array_map(static function ($arg) {
                        return static::toSafeString($arg);
                    }, $call['args'])) . ')', [self::COLOR_RED]);
                } else {
                    $tokens['{FUNCTION_ARGS}'] = $this->text('(...)', [$functionColor]);
                }
            }

            $this->output .= $this->replaceTokens($text, $tokens) . "\n";
        }

        if ($isShortened) {
            $this->output .= str_pad('...', 40, ' ', \STR_PAD_LEFT) . "\n";
        }
    }

    #[\Override]
    protected function processPreviousException(): void
    {
        if (!$this->exception->getPrevious()) {
            return;
        }

        $this->output .= "\n"
            . $this->text('Caused by Previous Exception:', [self::FORMAT_BOLD, self::BACKGROUND_COLOR_MAGENTA]) . "\n"
            . ((string) (new static($this->exception->getPrevious(), $this->adapter, $this->exception)));
    }
}
