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
     * @throws core\Exception
     *
     * @return mixed|object
     */
    public function addField($name, $seed = null)
    {
        $seed = $this->mergeSeeds($seed, ['FieldMock']);

        $field = $this->factory($seed, ['name' => $name], 'atk4\core\tests');

        return $this->_addIntoCollection($name, $field, 'fields');
    }

    public function hasField($name)
    {
        return $this->_hasInCollection($name, 'fields');
    }

    /**
     * @throws core\Exception
     *
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
