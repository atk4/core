<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * TODO move to anonymous class into HookTrait once https://github.com/phpstan/phpstan/issues/8741 is fixed.
 */
class HookInstanceWithoutConstructorCache
{
    /** @var array<class-string, object> */
    private static array $_instances = [];

    /**
     * @param class-string $class
     */
    public function getInstance(string $class): object
    {
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        }

        return self::$_instances[$class];
    }
}
