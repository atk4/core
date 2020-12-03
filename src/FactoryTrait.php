<?php

declare(strict_types=1);

namespace Atk4\Core;

/** @deprecated will be removed in v2.5 */
trait FactoryTrait
{
    /** @deprecated will be removed in v2.5 */
    private $_factoryTrait = true;

    /** @deprecated will be removed in v2.5 */
    public function mergeSeeds(...$seeds)
    {
        'trigger_error'('Method mergeSeeds is deprecated. Use Factory::mergeSeeds instead', E_USER_DEPRECATED);

        return Factory::mergeSeeds(...$seeds);
    }

    /** @deprecated will be removed in v2.5 */
    public function factory($seed, $defaults = []): object
    {
        'trigger_error'('Method factory is deprecated. Use Factory::factory instead', E_USER_DEPRECATED);

        return Factory::factory($seed, $defaults);
    }
}
