<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;
use Atk4\Core\TraitUtil;
use Atk4\Core\TranslatableTrait;
use Atk4\Core\Translator\ITranslatorAdapter;
use Atk4\Core\Translator\Translator;
use Composer\Autoload\ClassLoader;

/**
 * @phpstan-consistent-constructor
 */
abstract class RendererAbstract
{
    use TranslatableTrait;

    public \Throwable $exception;

    public ?\Throwable $parentException;

    public string $output = '';

    public ?ITranslatorAdapter $adapter;

    public function __construct(\Throwable $exception, ?ITranslatorAdapter $adapter = null, ?\Throwable $parentException = null)
    {
        $this->exception = $exception;
        $this->parentException = $parentException;
        $this->adapter = $adapter;
    }

    abstract protected function processHeader(): void;

    abstract protected function processParams(): void;

    abstract protected function processSolutions(): void;

    abstract protected function processStackTrace(): void;

    abstract protected function processStackTraceInternal(): void;

    abstract protected function processPreviousException(): void;

    protected function processAll(): void
    {
        $this->processHeader();
        $this->processParams();
        $this->processSolutions();
        $this->processStackTrace();
        $this->processPreviousException();
    }

    #[\Override]
    public function __toString(): string
    {
        try {
            $this->processAll();

            return $this->output;
        } catch (\Throwable $e) {
            // fallback if Exception occurred during rendering
            return '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
                . get_class($this->exception)
                . ($this->exception->getCode() !== 0 ? '(' . $this->exception->getCode() . ')' : '')
                . ': ' . $this->exception->getMessage() . ' !!';
        }
    }

    /**
     * @param array<string, string> $tokens
     */
    protected function replaceTokens(string $text, array $tokens): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    /**
     * @param array<string, mixed> $frame
     *
     * @return array<string, mixed>
     */
    protected function parseStackTraceFrame(array $frame): array
    {
        $parsed = [
            'line' => (string) ($frame['line'] ?? ''),
            'file' => (string) ($frame['file'] ?? ''),
            'class' => $frame['class'] ?? null,
            'object' => $frame['object'] ?? null,
            'function' => $frame['function'] ?? null,
            'args' => $frame['args'] ?? [],
            'class_formatted' => null,
            'object_formatted' => null,
        ];

        try {
            $parsed['file_rel'] = $this->makeRelativePath($parsed['file']);
        } catch (Exception $e) {
            $parsed['file_rel'] = $parsed['file'];
        }

        if ($parsed['class'] !== null) {
            $parsed['class_formatted'] = str_replace("\0", ' ', $this->tryRelativizePathsInString($parsed['class']));
        }

        if ($parsed['object'] !== null) {
            $parsed['object_formatted'] = TraitUtil::hasTrackableTrait($parsed['object'])
                ? get_object_vars($parsed['object'])['name'] ?? ($parsed['object']->shortName ?? '')
                : str_replace("\0", ' ', $this->tryRelativizePathsInString(get_class($parsed['object'])));
        }

        return $parsed;
    }

    /**
     * @param mixed $val
     */
    public static function toSafeString($val, bool $allowNl = false, int $maxDepth = 2): string
    {
        if (is_object($val)) {
            return get_class($val) . (TraitUtil::hasTrackableTrait($val)
                ? ' (' . (get_object_vars($val)['name'] ?? ($val->shortName ?? '')) . ')'
                : '');
        } elseif (str_replace(' (closed)', '', gettype($val)) === 'resource') {
            return get_debug_type($val);
        } elseif (is_scalar($val) || $val === null) {
            $out = json_encode($val, \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
            $out = preg_replace('~\\\"~', '"', preg_replace('~^"|"$~s', '\'', $out)); // use single quotes
            $out = preg_replace('~\\\{2}~s', '$1', $out); // unescape backslashes
            if ($allowNl) {
                $out = preg_replace('~(\\\r)?\\\n|\\\r~s', "\n", $out); // unescape new lines
            }

            return $out;
        }

        if ($maxDepth === 0) {
            return '...';
        }

        $out = '[';
        $suppressKeys = array_is_list($val);
        foreach ($val as $k => $v) {
            $kSafe = static::toSafeString($k);
            $vSafe = static::toSafeString($v, $allowNl, $maxDepth - 1);

            if ($allowNl) {
                $out .= "\n" . '  ' . ($suppressKeys ? '' : $kSafe . ': ') . preg_replace('~(?<=\n)~', '    ', $vSafe);
            } else {
                $out .= ($suppressKeys ? '' : $kSafe . ': ') . $vSafe;
            }

            if ($k !== array_key_last($val)) {
                $out .= $allowNl ? ',' : ', ';
            }
        }
        $out .= ($allowNl && count($val) > 0 ? "\n" : '') . ']';

        return $out;
    }

    protected function getExceptionTitle(): string
    {
        return $this->exception instanceof Exception
            ? $this->exception->getCustomExceptionTitle()
            : 'Critical Error';
    }

    protected function getExceptionMessage(): string
    {
        $msg = $this->exception->getMessage();
        $msg = $this->tryRelativizePathsInString($msg);
        $msg = $this->_($msg);

        return $msg;
    }

    /**
     * Returns stack trace and reindex it from the first call. If shortening is allowed,
     * shorten the stack trace if it starts with the parent one.
     *
     * @return array<int|'self', array<string, mixed>>
     */
    protected function getStackTrace(bool $shorten): array
    {
        $custTraceFx = static function (\Throwable $ex) {
            $trace = $ex->getTrace();

            return count($trace) > 0 ? array_combine(range(count($trace) - 1, 0, -1), $trace) : [];
        };

        $trace = $custTraceFx($this->exception);
        $parentTrace = $shorten && $this->parentException !== null ? $custTraceFx($this->parentException) : [];

        $bothAtk = $this->exception instanceof Exception && $this->parentException instanceof Exception;
        $c = min(count($trace), count($parentTrace));
        for ($i = 0; $i < $c; ++$i) {
            $cv = $this->parseStackTraceFrame($trace[$i]);
            $pv = $this->parseStackTraceFrame($parentTrace[$i]);

            if ($cv['line'] === $pv['line']
                && $cv['file'] === $pv['file']
                && $cv['class'] === $pv['class']
                && (!$bothAtk || $cv['object'] === $pv['object'])
                && $cv['function'] === $pv['function']
                && (!$bothAtk || $cv['args'] === $pv['args'])
            ) {
                unset($trace[$i]);
            } else {
                break;
            }
        }

        // display location as another stack trace call
        $trace = ['self' => [
            'line' => $this->exception->getLine(),
            'file' => $this->exception->getFile(),
        ]] + $trace;

        return $trace;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function _(string $message, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->adapter
            ? $this->adapter->_($message, $parameters, $domain, $locale)
            : Translator::instance()->_($message, $parameters, $domain, $locale);
    }

    protected function getVendorDirectory(): string
    {
        $loaderFile = realpath((new \ReflectionClass(ClassLoader::class))->getFileName());
        $coreDir = realpath(dirname(__DIR__, 2) . '/');
        if (str_starts_with($loaderFile, $coreDir . \DIRECTORY_SEPARATOR)) { // this repo is main project
            return realpath(dirname($loaderFile, 2) . '/');
        }

        return realpath(dirname(__DIR__, 4) . '/');
    }

    protected function makeRelativePath(string $path): string
    {
        $pathReal = $path === '' ? false : realpath($path);
        if ($pathReal === false) {
            throw new Exception('Path not found');
        }

        $filePathArr = explode(\DIRECTORY_SEPARATOR, ltrim($pathReal, '/\\'));
        $vendorRootArr = explode(\DIRECTORY_SEPARATOR, ltrim($this->getVendorDirectory(), '/\\'));
        if ($filePathArr[0] !== $vendorRootArr[0]) {
            return implode('/', $filePathArr);
        }

        array_pop($vendorRootArr); // assume parent directory as project directory
        while (isset($filePathArr[0]) && isset($vendorRootArr[0]) && $filePathArr[0] === $vendorRootArr[0]) {
            array_shift($filePathArr);
            array_shift($vendorRootArr);
        }

        return (count($vendorRootArr) > 0 ? str_repeat('../', count($vendorRootArr)) : '') . implode('/', $filePathArr);
    }

    protected function tryRelativizePathsInString(string $str): string
    {
        $str = preg_replace_callback('~(?<!\w)(?:[/\\\]|[a-z]:)\w?+[^:"\',;]*?\.php(?!\w)~i', function ($matches) {
            try {
                return $this->makeRelativePath($matches[0]);
            } catch (Exception $e) {
                return $matches[0];
            }
        }, $str);

        return $str;
    }
}
