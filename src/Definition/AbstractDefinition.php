<?php
declare(strict_types=1);

namespace atk4\core\Definition;

abstract class AbstractDefinition
{
    /** @var callable */
    protected $callable;

    /**
     * AbstractDependency constructor.
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
}
