<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core;

class CollectionMock
{
    use core\CollectionTrait;
    use core\FactoryTrait;

    protected $fields = [];

    /**
     * @return mixed|object
     */
    public function addField($name, $seed = null)
    {
        $seed = $this->mergeSeeds($seed, [FieldMock::class]);

        $field = $this->factory($seed, ['name' => $name]);

        return $this->_addIntoCollection($name, $field, 'fields');
    }

    public function tryGetField($name)
    {
        return $this->_tryGetFromCollection($name, 'fields');
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
