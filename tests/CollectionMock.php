<?php

declare(strict_types=1);

namespace atk4\core\Tests;

use atk4\core\CollectionTrait;
use atk4\core\Factory;

class CollectionMock
{
    use CollectionTrait;

    protected $fields = [];

    /**
     * @return mixed|object
     */
    public function addField($name, $seed = null)
    {
        $seed = Factory::mergeSeeds($seed, [FieldMock::class]);

        $field = Factory::factory($seed, ['name' => $name]);

        return $this->_addIntoCollection($name, $field, 'fields');
    }

    public function hasField($name): bool
    {
        return $this->_hasInCollection($name, 'fields');
    }

    /**
     * @return mixed
     */
    public function getField($name)
    {
        return $this->_getFromCollection($name, 'fields');
    }

    public function removeField($name)
    {
        $this->_removeFromCollection($name, 'fields');
    }
}
