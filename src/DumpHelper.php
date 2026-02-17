<?php

declare(strict_types=1);

namespace Atk4\Core;

use Atk4\Core\ExceptionRenderer\Html as HtmlExceptionRenderer;

/**
 * @internal
 */
class DumpHelper
{
    /** @var array<class-string, array<string, \ReflectionProperty>> */
    private static array $reflectionPropertiesCache = [];

    /** @var array<class-string, string> */
    private static array $formatClassCache = [];

    /** @var array<class-string, array<string, string>> */
    private static array $formatPropertyMangledNameCache = [];

    protected function getPropertyMangledName(\ReflectionProperty $reflectionProperty): string
    {
        // https://github.com/php/php-src/issues/18605#issuecomment-2894260586
        $key = $reflectionProperty->getName();
        if ($reflectionProperty->isPrivate()) {
            $key = "\0" . $reflectionProperty->getDeclaringClass()->getName() . "\0" . $key;
        } elseif ($reflectionProperty->isProtected()) {
            $key = "\0*\0" . $key;
        }

        return $key;
    }

    /**
     * @param class-string $class
     *
     * @return array<string, \ReflectionProperty>
     */
    protected function getReflectionProperties(string $class): array
    {
        $res = self::$reflectionPropertiesCache[$class] ?? null;
        if ($res !== null) {
            return $res;
        }

        $parentClass = get_parent_class($class);
        $res = $parentClass === false
            ? []
            : $this->getReflectionProperties($parentClass);

        foreach ((new \ReflectionClass($class))->getProperties() as $reflectionProperty) {
            if (\PHP_VERSION_ID >= 8_04_00 && $reflectionProperty->isVirtual()) {
                continue;
            }

            if (\PHP_VERSION_ID < 8_01_00) {
                $reflectionProperty->setAccessible(true);
            }

            $k = $this->getPropertyMangledName($reflectionProperty);
            $kProtected = "\0*\0" . $k;
            if (isset($res[$kProtected])) {
                assert(!isset($res[$k]));

                $pos = array_flip(array_keys($res))[$kProtected];
                $res = array_merge(
                    array_slice($res, 0, $pos, true),
                    [$k => $reflectionProperty],
                    array_slice($res, $pos + 1, null, true),
                );
            } else {
                $res[$k] = $reflectionProperty;
            }
        }

        self::$reflectionPropertiesCache[$class] = $res;

        return $res;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getObjectProperties(object $value): array
    {
        if (method_exists($value, '__debugInfo')) {
            return $value->__debugInfo();
        }

        $res = (array) $value;

        $reflectionProperties = $this->getReflectionProperties(get_class($value));

        $resFromCastKeys = array_keys($res);
        $classReflectionPropertiesKeys = array_keys($reflectionProperties);

        if ($resFromCastKeys === $classReflectionPropertiesKeys) {
            // optimization only
        } else {
            $resFromCast = $res;

            $res = [];
            foreach ($reflectionProperties as $reflectionProperty) {
                $k = $this->getPropertyMangledName($reflectionProperty);

                if (!array_key_exists($k, $resFromCast)) {
                    $res[$k] = null;
                } else {
                    $reflectionReference = \ReflectionReference::fromArrayElement($resFromCast, $k);
                    if ($reflectionReference !== null) {
                        $res[$k] = &$resFromCast[$k];
                    } else {
                        $res[$k] = $resFromCast[$k];
                    }
                }
            }

            foreach (array_diff($resFromCastKeys, $classReflectionPropertiesKeys) as $k) {
                assert(is_int($k) || !str_starts_with($k, "\0"));

                $reflectionReference = \ReflectionReference::fromArrayElement($resFromCast, $k);
                if ($reflectionReference !== null) {
                    $res[$k] = &$resFromCast[$k];
                } else {
                    $res[$k] = $resFromCast[$k];
                }
            }
        }

        // https://github.com/php/php-src/issues/18610
        if ($res === [$value]) {
            $res = [];
        }

        return $res;
    }

    /**
     * @param class-string $class
     */
    protected function formatClass(string $class): string
    {
        $res = self::$formatClassCache[$class] ?? null;
        if ($res !== null) {
            return $res;
        }

        assert($class === (new \ReflectionClass($class))->getName());

        $res = \Closure::bind(static function () use ($class) {
            return (new HtmlExceptionRenderer((new \ReflectionClass(\Exception::class))->newInstanceWithoutConstructor()))->formatClass($class);
        }, null, HtmlExceptionRenderer::class)();

        self::$formatClassCache[$class] = $res;

        return $res;
    }

    protected function formatPropertyMangledName(string $class, string $key): string
    {
        if (!str_starts_with($key, "\0")) {
            return $key;
        }

        $res = self::$formatPropertyMangledNameCache[$class][$key] ?? null;
        if ($res !== null) {
            return $res;
        }

        $pos = strpos($key, "\0", 1);
        assert($pos !== false);
        $extra = substr($key, 1, $pos - 1);
        $res = substr($key, $pos + 1);

        if (str_ends_with($extra, '@anonymous')) {
            $pos = strpos($res, "\0");
            assert($pos !== false);
            $extra = $extra . "\0" . substr($res, 0, $pos);
            $res = substr($res, $pos + 1);
        }

        if ($extra !== '*') {
            $reflectionProperties = array_filter($this->getReflectionProperties($class), static fn ($v) => $v->getName() === $res);
            if (count($reflectionProperties) === 1) {
                assert(array_key_first($reflectionProperties) === $key);
            } else {
                $res .= ':' . $this->formatClass($extra);
            }
        }

        self::$formatPropertyMangledNameCache[$class][$key] = $res;

        return $res;
    }

    /**
     * @param mixed                       $value
     * @param int<0, max>                 $maxDepth
     * @param array<int, positive-int>    $duplicateOids
     * @param array<string, positive-int> $duplicateRids
     */
    protected function findDuplicateOidsRids(&$value, ?string $rid, int $maxDepth, array &$duplicateOids, array &$duplicateRids, int $depth = 0): void
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

        if ($rid !== null) {
            $c = $duplicateRids[$rid] ?? 0;
            if ($c !== 0) {
                ++$duplicateRids[$rid];

                return;
            }

            $duplicateRids[$rid] = 1;
        }

        if (is_object($value) && $duplicateOids[$oid] === 1) {
            $v = $value;
            unset($value);
            $value = $this->getObjectProperties($v);
        }

        if (is_array($value) && count($value) <= 1 && $maxDepth < \PHP_INT_MAX) {
            ++$maxDepth;
        }

        if (is_array($value) && $depth < $maxDepth) {
            foreach (array_keys($value) as $k) {
                $reflectionReference = \ReflectionReference::fromArrayElement($value, $k);
                if ($reflectionReference !== null) {
                    $v = &$value[$k];
                    $rid = $reflectionReference->getId();
                } else {
                    $v = $value[$k];
                    $rid = null;
                }

                $this->findDuplicateOidsRids($v, $rid, $maxDepth, $duplicateOids, $duplicateRids, $depth + 1);

                unset($v);
            }
        }
    }

    protected function formatOid(int $value): string
    {
        return '#' . $value;
    }

    /**
     * @param int<-1, max> $value
     */
    protected function formatRidIndex(int $value): string
    {
        return '&' . ($value === -1 ? '' : $value);
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

        if (isset($types['false']) && isset($types['true'])) {
            unset($types['false']);
            unset($types['true']);
            $types['bool'] = 'bool';
        } elseif (isset($types['array']) && isset($types['list'])) {
            unset($types['list']);
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

        if (is_array($value)) {
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

    protected function makeIndent(int $depth): string
    {
        return str_repeat('    ', $depth);
    }

    /**
     * @param int|float|string $value
     */
    protected function printScalar($value, int $depth): void
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
        } elseif (is_float($value)) {
            $value = is_nan($value)
                ? 'NAN'
                : (string) $value;
        } elseif (is_string($value)) {
            $value = str_contains($value, "\n") || str_contains($value, "\r")
                ? "<<<'EOD'\n" . implode('', array_map(fn ($v) => $this->makeIndent($depth + 1) . $v, preg_split('~(?:\r\n?|\n)\K~', $value . "\nEOD")))
                : '\'' . preg_replace('~\\\(?=\\\|\')|\'~', '\\\$0', $value) . '\'';
        }

        echo $value;
    }

    /**
     * Improved version of native print_r() function.
     *
     * Objects and array references are printed only once.
     *
     * @param mixed       $value
     * @param int<0, max> $maxDepth
     *
     * @see https://github.com/php/php-src/blob/php-8.4.7/Zend/zend.c#L543
     */
    public function printReadable($value, int $maxDepth = 50): void
    {
        $duplicateOids = [];
        $duplicateRids = [];
        $this->findDuplicateOidsRids($value, null, $maxDepth, $duplicateOids, $duplicateRids);

        $duplicateRidsWithIndex = [];
        $i = -1;
        foreach ($duplicateRids as $k => $v) {
            $duplicateRidsWithIndex[$k] = [$v, $v === 1 ? -1 : ++$i];
        }

        ob_start();
        $flushed = false;
        try {
            $this->_printReadable($value, null, $maxDepth, $duplicateOids, $duplicateRidsWithIndex);
            echo "\n";

            $flushed = true;
            ob_end_flush();
        } finally {
            if (!$flushed) {
                ob_end_clean();
            }
        }
    }

    /**
     * @param mixed                                                 $value
     * @param int<0, max>                                           $maxDepth
     * @param array<int, -2|-1|int<1, max>>                         $duplicateOids
     * @param array<string, array{-2|-1|int<1, max>, int<-1, max>}> $duplicateRids
     */
    protected function _printReadable(&$value, ?string $rid, int $maxDepth, array &$duplicateOids, array &$duplicateRids, int $depth = 0): void
    {
        if (\PHP_VERSION_ID >= 8_05_00 && $rid === null) {
            $rid = '';
        }

        $isNewDuplicateRef = false;
        if (($duplicateRids[$rid][0] ?? 0) !== 0) {
            echo $this->formatRidIndex($duplicateRids[$rid][1]) . ' ';

            if ($duplicateRids[$rid][0] > 0) {
                $duplicateRids[$rid][0] = -1;
                $isNewDuplicateRef = true;
            }
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            $this->printScalar($value, $depth);

            return;
        }

        $type = $this->describeType($value);

        echo $type;

        if ($value === null || is_bool($value) || is_resource($value) || gettype($value) === 'resource (closed)') {
            return;
        }

        echo ' ';

        if (($duplicateRids[$rid][0] ?? 0) < 0 && !$isNewDuplicateRef) {
            echo $duplicateRids[$rid][0] < -1
                ? '*recursion*'
                : '*deduplicated*';

            return;
        }

        $isNewDuplicateObject = false;
        if (is_object($value)) {
            $oid = spl_object_id($value);
            if (($duplicateOids[$oid] ?? 1) !== 1) {
                if ($duplicateOids[$oid] < 0) {
                    echo $duplicateOids[$oid] < -1
                        ? '*recursion*'
                        : '*deduplicated*';

                    return;
                }

                $duplicateOids[$oid] = -1;
                $isNewDuplicateObject = true;
            }
        }

        $object = false;
        if (is_object($value)) {
            $object = $value;
            unset($value);
            $value = $this->getObjectProperties($object);
        }

        if (is_array($value) && count($value) <= 1 && $maxDepth < \PHP_INT_MAX) {
            ++$maxDepth;
        }

        ++$depth;

        echo $object !== false ? '{' : '[';

        if ($value !== []) {
            if ($depth > $maxDepth) {
                echo '...';
                echo $object !== false ? '}' : ']';

                return;
            }

            echo "\n";
        }

        foreach (array_keys($value) as $k) {
            $reflectionReference = \ReflectionReference::fromArrayElement($value, $k);
            if ($reflectionReference !== null) {
                $v = &$value[$k];
                $vRid = $reflectionReference->getId();
            } else {
                $v = $value[$k];
                $vRid = null;
            }

            echo $this->makeIndent($depth);

            if ($object !== false || !array_is_list($value)) {
                $this->printScalar($object !== false && is_string($k) ? $this->formatPropertyMangledName(get_class($object), $k) : $k, $depth);
                echo $object !== false ? ': ' : ' => ';
            }

            $reflectionProperty = $object !== false && $v === null
                ? $this->getReflectionProperties(get_class($object))[$k] ?? null
                : null;

            if ($reflectionProperty !== null && !$reflectionProperty->isInitialized($object)) {
                echo $reflectionProperty->hasType()
                    ? '*uninitialized*' // https://github.com/php/php-src/issues/18620
                    : '*unset*';
            } else {
                if ($isNewDuplicateRef) {
                    --$duplicateRids[$rid][0]; // @phpstan-ignore parameterByRef.type
                }
                if ($isNewDuplicateObject) {
                    --$duplicateOids[$oid]; // @phpstan-ignore variable.undefined, parameterByRef.type
                }

                try {
                    $this->_printReadable($v, $vRid, $maxDepth, $duplicateOids, $duplicateRids, $depth);
                } finally {
                    if ($isNewDuplicateRef) {
                        ++$duplicateRids[$rid][0]; // @phpstan-ignore parameterByRef.type
                    }
                    if ($isNewDuplicateObject) {
                        ++$duplicateOids[$oid]; // @phpstan-ignore variable.undefined, parameterByRef.type
                    }
                }
            }

            if ($k !== array_key_last($value)) {
                echo ',';
            }

            echo "\n";

            unset($v);
        }

        --$depth;

        if ($value !== []) {
            echo $this->makeIndent($depth);
        }
        echo $object !== false ? '}' : ']';
    }
}
