<?php


namespace atk4\core;


/**
 * This trait makes it possible for you to add child objects
 * into your object, but unlike "ContainerTrait" you can use
 * multiple collections stored as different array properties.
 *
 * This class does not offer automatic naming, so if you try
 * to add another element with same name, it will result in
 * exception.
 */
trait MultiContainerTrait
{

    /**
     * Use this method trait like this:
     *
     * function addField($name, $definition) {
     *     $field = $this->factory($definition, [], '\atk4\data\Field');
     *
     *     return $this->_addIntoCollection($name, $field, 'fields');
     * }
     *
     * @param $name string Name that can be used to reference object
     * @param $object mixed New element to add
     * @param $collection string String corresponding to the name of the property
     * @return $obect
     * @throws Exception
     */

    function _addIntoCollection(string $name, object $object, string $collection) {

        if (!$collection || !isset($this->$collection) || !is_array($this->$collection)) {
            throw new Exception([
                'Name of collection is specified incorrectly',
                'parent'=>$this,
                'collection'=>$collection
            ]);

        }

        if (!$name) {
            throw new Exception([
                'Object must be given a name when adding into this',
                'child'=>$object,
                'parent'=>$this,
                'collection'=>$collection
            ]);
        }

        if (isset($this->{$collection}[$name])) {
            throw new Exception([
                'Object with requested name already exist in collection',
                'name'=>$name,
                'collection'=>$collection,
                ]);

        }
        $this->{$collection}[$name] = $object;

        // Carry on reference to application if we have appScopeTraits set
        if (isset($this->_appScopeTrait) && isset($object->_appScopeTrait)) {
            $object->app = $this->app;
        }

        if (isset($object->_trackableTrait)) {
            $object->short_name = $name;
            $object->name = $this->
        }


        return $object;
    }

    function _removeFromCollection(string $name, string $collection) {

        unset($this->{$collection}[$name]);
    }

    function _hasInCollection(string $name, string $collection) {
        return $this->{$collection}[$name] ?? false;
    }

    /**
     * @param string $name
     * @param string $collection
     * @return object
     * @throws Exception
     */
    function _getFomCollection(string $name, string $collection)
    {
        if (!isset($this->{$collection}[$name])) {
            throw new Exception([
                'Element is not found in collection',
                'collection'=>$collection,
                'name'=>$name,
                'this'=>$this
            ]);
        }

        return $this->{$collection}[$name];
    }

}