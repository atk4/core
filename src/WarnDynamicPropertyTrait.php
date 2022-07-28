<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * This trait implements https://github.com/php/php-src/pull/7390 for lower PHP versions
 * and also emit a warning when isset() is called on undefined variable.
 */
trait WarnDynamicPropertyTrait
{
    protected function warnPropertyDoesNotExist(string $name): void
    {
        // match native PHP behaviour as much as possible
        // https://3v4l.org/2KR3m
        $class = static::class;
        try {
            $propRefl = new \ReflectionProperty($class, $name);
            if (!$propRefl->isPrivate() && !$propRefl->isPublic() && !$propRefl->isStatic()) {
                throw new \Error('Cannot access protected property ' . $class . '::$' . $name);
            }
        } catch (\ReflectionException $e) {
        }

        'trigger_error'('Undefined property: ' . get_debug_type($this) . '::$' . $name, \E_USER_WARNING);
    }

    public function __isset(string $name): bool
    {
        $this->warnPropertyDoesNotExist($name);

        return isset($this->{$name});
    }

    /**
     * @return mixed
     */
    public function &__get(string $name)
    {
        $this->warnPropertyDoesNotExist($name);

        return $this->{$name};
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $this->warnPropertyDoesNotExist($name);

        $this->{$name} = $value;
    }

    public function __unset(string $name): void
    {
        $this->warnPropertyDoesNotExist($name);

        unset($this->{$name});
    }
}
