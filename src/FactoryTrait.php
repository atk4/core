<?php

declare(strict_types=1);

namespace atk4\core;

/**
 * @deprecated will be removed in 2021-jun
 */
trait FactoryTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_factoryTrait = true;

    /**
     * See \atk4\core\Factory::mergeSeeds().
     *
     * @deprecated will be removed in 2021-jun
     */
    public function mergeSeeds(...$seeds)
    {
        return Factory::mergeSeeds(...$seeds);
    }

    /**
     * See \atk4\core\Factory::factory().
     *
     * @deprecated will be removed in 2021-jun
     */
    public function factory($seed, $defaults = []): object
    {
        return Factory::factory($seed, $defaults);
    }
}
