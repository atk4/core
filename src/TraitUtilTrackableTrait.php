<?php

declare(strict_types=1);

namespace Atk4\Core;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilTrackableTrait
{
    public function issetOwner(): bool;

    public function getOwner(): object;

    /**
     * @return $this
     */
    public function setOwner(object $owner);

    /**
     * @return $this
     */
    public function unsetOwner();

    public function getDesiredName(): string;

    public function destroy(): void;
}
