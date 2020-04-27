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
trait CollectionTrait
{
    /**
     * Use this method trait like this:.
     *
     * function addField($name, $definition) {
     *     $field = $this->factory($definition, [], '\atk4\data\Field');
     *
     *     return $this->_addIntoCollection($name, $field, 'fields');
     * }
     *
     * @param string $name       Name that can be used to reference object
     * @param object $object     New element to add
     * @param string $collection string String corresponding to the name of the property
     *
     * @throws Exception
     *
     * @return object
     */
    public function _addIntoCollection(string $name, object $object, string $collection)
    {
        if (!$collection || !isset($this->{$collection}) || !is_array($this->{$collection})) {
            throw new Exception([
                'Name of collection is specified incorrectly',
                'parent'    => $this,
                'collection'=> $collection,
            ]);
        }

        if (!$name) {
            throw new Exception([
                'Object must be given a name when adding into this',
                'child'     => $object,
                'parent'    => $this,
                'collection'=> $collection,
            ]);
        }

        if ($this->_hasInCollection($name, $collection) !== false) {
            throw new Exception([
                'Object with requested name already exist in collection',
                'name'      => $name,
                'collection'=> $collection,
            ]);
        }
        $this->{$collection}[$name] = $object;

        // Carry on reference to application if we have appScopeTraits set
        if (isset($this->_appScopeTrait) && isset($object->_appScopeTrait)) {
            $object->app = $this->app;
        }

        // Calculate long "name" but only if both are trackables
        if (isset($object->_trackableTrait)) {
            $object->short_name = $name;
            $object->owner = $this;
            if (isset($this->_trackableTrait)) {
                $object->name = $this->_shorten_ml($this->name . '-' . $collection . '_' . $name);
            }
        }

        if (isset($object->_initializerTrait)) {
            if (!$object->_initialized) {
                $object->init();
            }
            if (!$object->_initialized) {
                throw new Exception([
                    'You should call parent::init() when you override initializer',
                    'object'=> $object,
                ]);
            }
        }

        return $object;
    }

    /**
     * Removes element from specified collection.
     *
     * @throws Exception
     */
    public function _removeFromCollection(string $name, string $collection): void
    {
        if ($this->_hasInCollection($name, $collection) === false) {
            throw new Exception([
                'Element by this name is NOT in the collection, cannot remove',
                'parent'    => $this,
                'collection'=> $collection,
                'name'      => $name,
            ]);
        }
        unset($this->{$collection}[$name]);
    }

    /**
     * Call this on collections after cloning object. This will clone all collection
     * elements (which are objects).
     *
     * @param string Collection to be cloned
     */
    public function _cloneCollection(string $collection): void
    {
        foreach ($this->{$collection} as &$object) {
            $object = clone $object;
            if (isset($object->owner)) {
                $object->owner = $this;
            }
        }
    }

    /**
     * Returns object from collection or false if object is not found.
     *
     * @return object|false
     */
    public function _hasInCollection(string $name, string $collection)
    {
        return $this->{$collection}[$name] ?? false;
    }

    /**
     * @throws Exception
     */
    public function _getFromCollection(string $name, string $collection): object
    {
        $object = $this->_hasInCollection($name, $collection);
        if (false === $object) {
            throw new Exception([
                'Element is not found in collection',
                'collection'=> $collection,
                'name'      => $name,
                'this'      => $this,
            ]);
        }

        return $object;
    }

    /**
     * Method used internally for shortening object names
     * Identical implementation to ContainerTrait::_shorten.
     *
     * @param string $desired desired name of new object
     *
     * @return string shortened name of new object
     */
    protected function _shorten_ml(string $desired): string
    {
        if (
            isset($this->_appScopeTrait) &&
            isset($this->app->max_name_length) &&
            strlen($desired) > $this->app->max_name_length
        ) {

            /*
             * Basic rules: hash is 10 character long (8+2 for separator)
             * We need at least 5 characters on the right side. Total must not exceed
             * max_name_length. First chop will be max-10, then chop size will increase by
             * max-15
             */
            $len = strlen($desired);
            $left = $len - ($len - 10) % ($this->app->max_name_length - 15) - 5;

            $key = substr($desired, 0, $left);
            $rest = substr($desired, $left);

            if (!isset($this->app->unique_hashes[$key])) {
                $this->app->unique_hashes[$key] = '_' . dechex(crc32($key));
            }
            $desired = $this->app->unique_hashes[$key] . '__' . $rest;
        }

        return $desired;
    }
}
