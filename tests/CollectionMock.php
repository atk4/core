<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\CollectionTrait;
use Atk4\Core\Factory;

class CollectionMock
{
    use CollectionTrait;

    /** @var array<string, FieldMock> */
    protected $fields = [];

    /**
     * @param array<mixed>|object|null $seed
     */
    public function addField(string $name, $seed = null): FieldMock
    {
        $seed = Factory::mergeSeeds($seed, [FieldMock::class]);

        $field = Factory::factory($seed);

        $shortNameProp = $field instanceof FieldMockCustom ? 'shortName' : 'name';
        Factory::factory($seed, [$shortNameProp => $name]);

        return $this->_addIntoCollection($name, $field, 'fields');
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
