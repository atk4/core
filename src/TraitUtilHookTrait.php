<?php

declare(strict_types=1);

namespace Atk4\Core;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilHookTrait
{
    /**
     * @param array<int, mixed> $args
     */
    public function onHook(string $spot, \Closure $fx, array $args = [], int $priority = 5): int;

    /**
     * @param array<int, mixed> $args
     */
    public function onHookShort(string $spot, \Closure $fx, array $args = [], int $priority = 5): int;

    /**
     * @param array<int, mixed> $args
     */
    public function onHookDynamic(string $spot, \Closure $getFxThisFx, \Closure $fx, array $args = [], int $priority = 5): int;

    /**
     * @param array<int, mixed> $args
     */
    public function onHookDynamicShort(string $spot, \Closure $getFxThisFx, \Closure $fx, array $args = [], int $priority = 5): int;

    /**
     * @return static
     */
    public function removeHook(string $spot, int $priority = null, bool $priorityIsIndex = false);

    public function hookHasCallbacks(string $spot, int $priority = null, bool $priorityIsIndex = false): bool;

    /**
     * @param array<int, mixed> $args
     *
     * @return array<int, mixed>|mixed
     */
    public function hook(string $spot, array $args = [], HookBreaker &$brokenBy = null);

    /**
     * @param mixed $return
     */
    public function breakHook($return): void;
}
