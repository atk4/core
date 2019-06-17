<?php

namespace atk4\core\tests;

use atk4\core;

class MultiContainerMock
{
    use core\MultiContainerTrait;
    use core\FactoryTrait;

    protected $fields = [];

    /**
     * @param $name
     * @param $seed
     * @return mixed|object
     * @throws core\Exception
     */
    public function addField($name, $seed = null)
    {
        $seed = $this->mergeSeeds($seed, ['FieldMock']);

        $field = $this->factory($seed, ['name'=>$name], '\atk4\core\tests');

        return $this->_addIntoCollection($name, $field, 'fields');
    }

    public function hasField($name)
    {
        return $this->_hasInCollection($name, 'fields');
    }

    /**
     * @param $name
     * @return mixed
     * @throws core\Exception
     */
    public function getField($name) {
        return $this->_getFomCollection($name, 'fields');
    }

    public function removeField($name)
    {
        $this->_removeFromCollection($name, 'fields');
    }
}
