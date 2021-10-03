<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * This trait implements https://github.com/php/php-src/pull/7390 .
 *
 * We also add this trait to the most commonly used DiContainerTrait trait to help the developer
 * to discover removed/renamed properties and/or general typos.
 *
 * Remove once PHP 8.1 is no longer supported (the PR above is expected to be merged into PHP 8.2).
 */
trait WarnDynamicPropertyTrait
{
    protected function warnPropertyDoesNotExist(string $name): void
    {
        'trigger_error'('Property ' . static::class . '::$' . $name . ' does not exist', \E_USER_DEPRECATED);
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
