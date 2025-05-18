<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Core\ExceptionRenderer\Html as HtmlExceptionRenderer;

/**
 * @internal
 */
class DumpHelper
{
    protected function formatClass(string $class): string
    {
        return \Closure::bind(static function () use ($class) {
            return (new HtmlExceptionRenderer((new \ReflectionClass(\Exception::class))->newInstanceWithoutConstructor()))->formatClass($class);
        }, null, HtmlExceptionRenderer::class)();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getObjectProperties(object $value): array
    {
        $reflProperties = [];
        $class = get_class($value);
        do {
            $reflClass = new \ReflectionClass($class);
            foreach ($reflClass->getProperties() as $relfProperty) {
                if (!$relfProperty->isStatic()) {
                    if (!isset($reflProperties[$relfProperty->getName()]) || $relfProperty->isPrivate()) {
                        $reflProperties[$relfProperty->getName()][] = $relfProperty;
                    }
                }
            }
        } while (($class = get_parent_class($class)) !== false);

        foreach (get_object_vars($value) as $k => $v) {
            if (!isset($reflProperties[$k])) {
                $reflProperties[$k . "\n"] = true;
            }
        }

        ksort($reflProperties);

        $res = [];
        foreach ($reflProperties as $k => $reflProperties2) {
            if (str_ends_with($k, "\n")) {
                $k = substr($k, 0, -1);

                $res[$k] = &$value->{$k};

                continue;
            }

            foreach (array_reverse($reflProperties2) as $relfProperty) {
                $name = $relfProperty->getName();
                $class = $relfProperty->getDeclaringClass()->getName();

                $k = $name;
                if ($relfProperty->isPrivate() && count($reflProperties2) > 1) {
                    $k .= ':' . $this->formatClass($class);
                }

                if (\PHP_VERSION_ID < 8_01_00) {
                    $relfProperty->setAccessible(true);
                }

                if (!$relfProperty->isInitialized($value)) {
                    $res[$k] = null;
                } else {
                    $res[$k] = &\Closure::bind(static function &() use (&$value, $name) {
                        return $value->{$name};
                    }, null, $class)();
                }
            }
        }

        return $res;
    }

    /**
     * @param mixed $value
     */
    private function getRid(&$value): string
    {
        return \ReflectionReference::fromArrayElement([&$value], 0)->getId();
    }

    /**
     * @param mixed                       $value
     * @param mixed                       $rootValue
     * @param array<int, positive-int>    $duplicateOids
     * @param array<string, positive-int> $duplicateRids
     */
    protected function findDuplicateOidsRids(&$value, &$rootValue, int $maxDepth, array &$duplicateOids, array &$duplicateRids, int $depth = 0): void
    {
        if (is_object($value)) {
            $oid = spl_object_id($value);
            $c = $duplicateOids[$oid] ?? 0;
            if ($c === 0) {
                $duplicateOids[$oid] = 1;
            } else {
                ++$duplicateOids[$oid];
            }
        }

        $rid = $this->getRid($value);
        $c = $duplicateRids[$rid] ?? 0;
        if ($c !== 0) {
            ++$duplicateRids[$rid];

            return;
        }

        $duplicateRids[$rid] = 1;

        $ridRootValue = $this->getRid($rootValue);
        if (is_array($value) && $value === $rootValue && $rid !== $ridRootValue) { // TODO fix compare when NAN is present https://github.com/php/php-src/issues/18563
            $rootValue = &$value;
            $duplicateOids = [];
            $duplicateRids = [];
            $this->findDuplicateOidsRids($rootValue, $rootValue, $maxDepth, $duplicateOids, $duplicateRids);

            return;
        }

        if (is_object($value) && $duplicateOids[$oid] === 1) { // @phpstan-ignore variable.undefined
            $v = $value;
            unset($value);
            $value = $this->getObjectProperties($v);
        }

        if (is_array($value) && $depth < $maxDepth) {
            foreach ($value as &$v) {
                $this->findDuplicateOidsRids($v, $rootValue, $maxDepth, $duplicateOids, $duplicateRids, $depth + 1);

                if ($this->getRid($rootValue) !== $ridRootValue) {
                    break;
                }
            }
        }
    }

    protected function formatOid(int $oid): string
    {
        return '#' . $oid;
    }

    protected function formatRid(string $rid): string
    {
        return '&' . $rid;
    }

    /**
     * @param mixed ...$values
     */
    private function describeTypeShallow(...$values): string
    {
        $types = [];

        foreach ($values as $value) {
            if (is_bool($value)) {
                $type = $value
                    ? 'true'
                    : 'false';
            } elseif (is_array($value) && array_is_list($value)) {
                $type = 'list';
            } elseif (is_object($value)) {
                $type = $this->formatClass(get_class($value));
            } elseif (is_resource($value) || gettype($value) === 'resource (closed)') {
                $type = 'resource';
            } else {
                $type = get_debug_type($value);
            }

            $types[$type] = $type;
        }

        sort($types);

        return implode('|', $types);
    }

    /**
     * @param mixed $value
     */
    protected function describeType($value): string
    {
        $type = $this->describeTypeShallow($value);

        if ($value === '') {
            $type = 'empty-string';
        } elseif (is_array($value)) {
            if ($value === []) {
                $type = 'empty-array';
            } else {
                $type .= '<'
                    . (array_is_list($value) ? '' : $this->describeTypeShallow(...array_keys($value)) . ', ')
                    . $this->describeTypeShallow(...array_values($value))
                    . '>';
            }
        } elseif (is_object($value)) {
            if ($value instanceof \WeakReference) {
                $v = $value->get();
                $type .= '<'
                    . ($v === null ? '*destroyed*' : $this->describeTypeShallow($v))
                    . (is_object($v) ? $this->formatOid(spl_object_id($v)) : '')
                    . '>';
            }

            $type .= $this->formatOid(spl_object_id($value));
        } elseif (is_resource($value) || gettype($value) === 'resource (closed)') {
            $type .= '<'
                . (gettype($value) === 'resource (closed)' ? '*closed*' : substr(get_debug_type($value), strlen('resource ('), -1))
                . '>';
        }

        return $type;
    }

    /**
     * Improved version of native print_r() function.
     *
     * Objects and array references are printed only once.
     *
     * https://github.com/php/php-src/blob/php-8.4.7/Zend/zend.c#L543
     *
     * @param mixed $value
     */
    public function printReadable($value): void
    {
        $rootValue = &$value;
        $duplicateOids = [];
        $duplicateRids = [];
        $this->findDuplicateOidsRids($value, $rootValue, \PHP_INT_MAX, $duplicateOids, $duplicateRids);

        $duplicateOids = array_diff($duplicateOids, [1]);
        $duplicateRids = array_diff($duplicateRids, [1]);

        $this->_printReadable($rootValue, $duplicateOids, $duplicateRids);
        echo "\n";
    }

    /**
     * @param mixed                       $value
     * @param array<int, positive-int>    $duplicateOids
     * @param array<string, positive-int> $duplicateRids
     */
    protected function _printReadable(&$value, array $duplicateOids, array $duplicateRids, int $depth = 0): void
    {
        if (is_int($value) || is_float($value) || is_string($value)) {
            $this->printScalar($value);

            return;
        }

        $type = $this->describeType($value);

        echo $type;

        if ($value === null || is_bool($value) || is_resource($value) || gettype($value) === 'resource (closed)') {
            return;
        }

        echo ' ';

        $isObject = false;
        if (is_object($value)) {
            $value = $this->getObjectProperties($value);
            $isObject = true;
        }

        echo $isObject ? '{' : '[';

        if ($value !== []) {
            echo "\n";
        }

        foreach ($value as $k => $v) {
            echo str_repeat('    ', $depth + 1);

            $this->printScalar($k);
            echo $isObject ? ': ' : ' => ';

            $this->_printReadable($v, $duplicateOids, $duplicateRids, $depth + 1);

            if ($k !== array_key_last($value)) {
                echo ',';
            }

            echo "\n";
        }

        echo $isObject ? '}' : ']';
    }

    /**
     * @param int|float|string $value
     */
    protected function printScalar($value): void
    {
        if (is_int($value) || (is_float($value) && is_finite($value))) {
            if (is_int($value)) {
                $str = (string) $value;
            } else {
                $precisionBackup = ini_get('precision');
                ini_set('precision', '-1');
                try {
                    $str = (string) $value;
                } finally {
                    ini_set('precision', $precisionBackup);
                }
            }

            if (str_contains($str, '.')) {
                $decimal = substr($str, strpos($str, '.'));
                $str = substr($str, 0, -strlen($decimal));
            } elseif (is_float($value)) {
                $decimal = '.0';
            } else {
                $decimal = false;
            }

            $value = strrev(implode('_', str_split(strrev($str), 3)))
                . ($decimal === false ? '' : $decimal);
        } elseif (is_string($value)) {
            $value = '\'' . preg_replace('~\\\(?=\\\|\')|\'~', '\\\$0', $value) . '\'';
        }

        echo $value;
    }
}
