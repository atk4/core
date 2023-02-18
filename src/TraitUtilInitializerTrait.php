<?php

declare(strict_types=1);

namespace Atk4\Core;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilInitializerTrait
{
    public function init(): void;

    public function isInitialized(): bool;

    public function assertIsInitialized(): void;

    public function invokeInit(): void;
}
