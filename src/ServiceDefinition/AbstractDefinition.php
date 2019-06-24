<?php

declare(strict_types=1);

namespace atk4\core\ServiceDefinition;

abstract class AbstractDefinition
{
    /** @var callable */
    protected $callable;

    /**
     * AbstractDefinition constructor.
     *
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param iDefiner $iDefiner
     *
     * @return object
     */
    public function process(iDefiner $iDefiner)
    {
        return ($this->callable)($iDefiner);
    }

    /**
     * ShortHand for classes that need only to call Constructor.
     *
     * @param string $classname             Name of the class to be instantiated
     * @param mixed  ...$constructArguments Arguments for the __construct method
     *
     * @return static
     */
    public static function fromClassName(string $classname, ...$constructArguments) :self
    {
        return new static(function (iDefiner $c) use ($classname, $constructArguments) {
            return new $classname(...$constructArguments);
        });
    }
}
