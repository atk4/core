<?php

declare(strict_types=1);

namespace Atk4\Core;

return false;

return false; // @phpstan-ignore-line
interface TraitUtilDiContainerTrait
{
    /**
     * @param array<string, mixed> $properties
     *
     * @return $this
     */
    public function setDefaults(array $properties, bool $passively = false);

    /**
     * @param mixed $value
     */
    public function setMissingProperty(string $propertyName, $value): void;

    /**
     * @return static
     */
    public static function assertInstanceOf(object $object);

    /**
     * @param array<mixed>|object $seed
     * @param array<mixed>        $defaults
     *
     * @return static
     */
    public static function fromSeed($seed = [], $defaults = []);

    /**
     * @param array<mixed>|object $seed
     * @param array<mixed>        $defaults
     *
     * @return static
     */
    public static function fromSeedUnsafe($seed = [], $defaults = []);
}
