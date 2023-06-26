<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * Object wrapper with __debugInfo() method mapped to __debugInfoQuiet().
 *
 * @template-covariant T of object
 */
class QuietObjectWrapper
{
    /** @var T */
    private object $obj;

    /**
     * @param T $obj
     */
    public function __construct(object $obj)
    {
        $this->obj = $obj;
    }

    private function __clone()
    {
        // prevent cloning
    }

    public function __sleep(): array
    {
        throw new Exception('Serialization is not supported');
    }

    /**
     * @return T
     */
    public function get(): object
    {
        return $this->obj;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $obj = $this->obj;

        $res = ['wrappedClass' => get_debug_type($obj)];
        if (method_exists($obj, '__debugInfoQuiet')) {
            $res = array_merge($res, $obj->__debugInfoQuiet());
        }

        return $res;
    }
}
