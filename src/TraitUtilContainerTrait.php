<?php

declare(strict_types=1);

namespace Atk4\Core;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilContainerTrait
{
    public function _uniqueElementName(string $desired): string;

    /**
     * @param object|array<mixed, mixed> $obj
     * @param array<mixed, mixed>|string $args
     */
    public function add($obj, $args = []): object;

    /**
     * @param array{desired_name?: string, name?: string} $args
     */
    public function _addContainer(object $element, array $args): void;

    /**
     * @param string|object $shortName
     *
     * @return $this
     */
    public function removeElement($shortName);

    public function _shorten(string $ownerName, string $itemShortName, ?string $origItemName): string;

    public function getElement(string $shortName): object;

    /**
     * @param string $shortName
     */
    public function hasElement($shortName): bool;
}
