<?php

declare(strict_types=1);

namespace Atk4\Core\ExceptionRenderer;

use Atk4\Core\Exception;
use Atk4\Core\TraitUtil;
use Atk4\Core\TranslatableTrait;
use Atk4\Core\Translator\ITranslatorAdapter;
use Atk4\Core\Translator\Translator;

abstract class RendererAbstract
{
    use TranslatableTrait;

    /** @var \Throwable|Exception */
    public $exception;

    /** @var \Throwable|Exception|null */
    public $parent_exception;

    /** @var string */
    public $output = '';

    /** @var ITranslatorAdapter|null */
    public $adapter;

    public function __construct(\Throwable $exception, ITranslatorAdapter $adapter = null, \Throwable $parent_exception = null)
    {
        $this->adapter = $adapter;
        $this->exception = $exception;
        $this->parent_exception = $parent_exception;
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

    public function __toString(): string
    {
        try {
            $this->processAll();

            return $this->output;
        } catch (\Throwable $e) {
            // fallback if Exception occur in renderer
            return '!! ATK4 CORE ERROR - EXCEPTION RENDER FAILED: '
                . get_class($this->exception)
                . ($this->exception->getCode() !== 0 ? '(' . $this->exception->getCode() . ')' : '')
                . ': ' . $this->exception->getMessage() . ' !!';
        }
    }

    protected function replaceTokens(array $tokens, string $text): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    protected function parseStackTraceCall(array $call): array
    {
        $parsed = [
            'line' => (string) ($call['line'] ?? ''),
            'file' => (string) ($call['file'] ?? ''),
            'class' => $call['class'] ?? null,
            'object' => $call['object'] ?? null,
            'function' => $call['function'] ?? null,
            'args' => $call['args'] ?? [],
            'object_formatted' => null,
            'file_formatted' => null,
            'line_formatted' => null,
        ];

        try {
            $parsed['file_rel'] = $this->makeRelativePath($parsed['file']);
        } catch (Exception $e) {
            $parsed['file_rel'] = $parsed['file'];
        }

        if ($parsed['object'] !== null) {
            $parsed['object_formatted'] = TraitUtil::hasTrackableTrait($parsed['object'])
                ? get_object_vars($parsed['object'])['name']
                : get_class($parsed['object']);
        }

        return $parsed;
    }

    public static function toSafeString($val, $allowNl = false, int $maxDepth = 2): string
    {
        if ($val instanceof \Closure) {
            return 'closure';
        } elseif (is_object($val)) {
            return get_class($val) . (\Atk4\Core\TraitUtil::hasTrackableTrait($val) ? ' (' . get_object_vars($val)['name'] . ')' : '');
        } elseif (is_resource($val)) {
            return 'resource';
        } elseif (is_scalar($val) || $val === null) {
            $out = json_encode($val, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
            $out = preg_replace('~\\\\"~', '"', preg_replace('~^"|"$~s', '\'', $out)); // use single quotes
            $out = preg_replace('~\\\\([\\\\/])~s', '$1', $out); // unescape slashes
            if ($allowNl) {
                $out = preg_replace('~(\\\\r)?\\\\n|\\\\r~s', "\n", $out); // unescape new lines
            }

            return $out;
        }

        if ($maxDepth === 0) {
            return '...';
        }

        $out = '[';
        $supressKeys = array_is_list($val);
        foreach ($val as $k => $v) {
            $kSafe = static::toSafeString($k);
            $vSafe = static::toSafeString($v, $allowNl, $maxDepth - 1);

            if ($allowNl) {
                $out .= "\n" . '  ' . ($supressKeys ? '' : $kSafe . ': ') . preg_replace('~(?<=\n)~', '    ', $vSafe);
            } else {
                $out .= ($supressKeys ? '' : $kSafe . ': ') . $vSafe;
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
     */
    protected function getStackTrace(bool $shorten): array
    {
        $custTraceFunc = function (\Throwable $ex) {
            $trace = $ex instanceof Exception
                ? $ex->getMyTrace()
                : $ex->getTrace();

            return count($trace) > 0 ? array_combine(range(count($trace) - 1, 0, -1), $trace) : [];
        };

        $trace = $custTraceFunc($this->exception);
        $parent_trace = $shorten && $this->parent_exception !== null ? $custTraceFunc($this->parent_exception) : [];

        $both_atk = $this->exception instanceof Exception && $this->parent_exception instanceof Exception;
        $c = min(count($trace), count($parent_trace));
        for ($i = 0; $i < $c; ++$i) {
            $cv = $this->parseStackTraceCall($trace[$i]);
            $pv = $this->parseStackTraceCall($parent_trace[$i]);

            if ($cv['line'] === $pv['line']
                    && $cv['file'] === $pv['file']
                    && $cv['class'] === $pv['class']
                    && (!$both_atk || $cv['object'] === $pv['object'])
                    && $cv['function'] === $pv['function']
                    && (!$both_atk || $cv['args'] === $pv['args'])) {
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

    public function _($message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->adapter
            ? $this->adapter->_($message, $parameters, $domain, $locale)
            : Translator::instance()->_($message, $parameters, $domain, $locale);
    }

    protected function getVendorDirectory(): string
    {
        $loaderFile = realpath((new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName());
        $coreDir = realpath(dirname(__DIR__, 2) . '/');
        if (strpos($loaderFile, $coreDir . \DIRECTORY_SEPARATOR) === 0) { // this repo is main project
            return realpath(dirname($loaderFile, 2) . '/');
        }

        return realpath(dirname(__DIR__, 4) . '/');
    }

    protected function makeRelativePath(string $path): string
    {
        if ($path === '' || ($pathReal = realpath($path)) === false) {
            throw new Exception('Path not found');
        }

        $filePathArr = explode(\DIRECTORY_SEPARATOR, ltrim($pathReal, '/\\'));
        $vendorRootArr = explode(\DIRECTORY_SEPARATOR, ltrim($this->getVendorDirectory(), '/\\'));
        if ($filePathArr[0] !== $vendorRootArr[0]) {
            return $filePathArr;
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
        $str = preg_replace_callback('~(?<!\w)(?:[/\\\\]|[a-z]:)\w?+[^:"\',;]*?\.php(?!\w)~i', function ($matches) {
            try {
                return $this->makeRelativePath($matches[0]);
            } catch (\Exception $e) {
                return $matches[0];
            }
        }, $str);

        return $str;
    }
}
