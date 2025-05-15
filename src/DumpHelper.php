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
                $reflProperties[$k . "\n"] = $v;
            }
        }

        ksort($reflProperties);

        $res = [];
        foreach ($reflProperties as $k => $reflProperties2) {
            if (is_string($k) && str_ends_with($k, "\n")) {
                $res[substr($k, 0, -1)] = $reflProperties2;

                continue;
            }

            foreach (array_reverse($reflProperties2) as $relfProperty) {
                $name = $relfProperty->getName();
                if ($relfProperty->isPrivate() && count($reflProperties2) > 1) {
                    $name .= ':' . $this->formatClass($relfProperty->getDeclaringClass()->getName());
                }

                if (\PHP_MAJOR_VERSION === 7) {
                    $relfProperty->setAccessible(true);
                }
                $res[$name] = $relfProperty->isInitialized($value)
                    ? $relfProperty->getValue($value)
                    : null;
            }
        }

        return $res;
    }

    /**
     * @param mixed $value
     */
    private function getRid(&$value): ?string
    {
        return \ReflectionReference::fromArrayElement([&$value], 0)->getId();
    }

    /**
     * @param mixed $value
     */
    protected function findDuplicateOidsRids(&$value, int $maxDepth, array &$duplicateOids, array &$duplicateRids, int $depth = 0): void
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

        if (is_object($value) && $duplicateOids[$oid] === 1) {
            $v = $value;
            unset($value);
            $value = $this->getObjectProperties($v);
        }

        if (is_array($value) && $depth < $maxDepth) {
            foreach ($value as &$v) {
                $this->findDuplicateOidsRids($v, $maxDepth, $duplicateOids, $duplicateRids, $depth + 1);
            }
        }
    }
}
