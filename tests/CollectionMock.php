<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\CollectionTrait;
use Atk4\Core\Factory;

class CollectionMock
{
    use CollectionTrait;

    /** @var array<string, FieldMock> */
    protected array $fields = [];

    /**
     * @param array<mixed>|FieldMock|null $seed
     */
    public function addField(string $name, $seed = null): FieldMock
    {
        $seed = Factory::mergeSeeds($seed, [FieldMock::class]);

        $field = Factory::factory($seed);

        $this->_addIntoCollection($name, $field, 'fields');

        return $field;
    }

    public function hasField(string $name): bool
    {
        return $this->_hasInCollection($name, 'fields');
    }

    public function getField(string $name): FieldMock
    {
        return $this->_getFromCollection($name, 'fields');
    }

    public function removeField(string $name): void
    {
        $this->_removeFromCollection($name, 'fields');
    }
}
