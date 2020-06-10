<?php

declare(strict_types=1);

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
     *     $field = Field::fromSeed($seed);
     *
     *     return $this->_addIntoCollection($name, $field, 'fields');
     * }
     *
     * @param string $collection property name
     */
    public function _addIntoCollection(string $name, object $item, string $collection): object
    {
        if (!isset($this->{$collection}) || !is_array($this->{$collection})) {
            throw (new Exception('Collection does NOT exist'))
                ->addMoreInfo('collection', $collection);
        }

        if ($name === '') {
            throw (new Exception('Empty name is not supported'))
                ->addMoreInfo('collection', $collection)
                ->addMoreInfo('name', $name);
        }

        if ($this->_tryGetFromCollection($name, $collection) !== null) {
            throw (new Exception('Element with the same name already exist in the collection'))
                ->addMoreInfo('collection', $collection)
                ->addMoreInfo('name', $name);
        }
        $this->{$collection}[$name] = $item;

        // Carry on reference to application if we have appScopeTraits set
        if (isset($this->_appScopeTrait) && isset($item->_appScopeTrait)) {
            $item->app = $this->app;
        }

        // Calculate long "name" but only if both are trackables
        if (isset($item->_trackableTrait)) {
            $item->short_name = $name;
            $item->owner = $this;
            if (isset($this->_trackableTrait)) {
                $item->name = $this->_shorten_ml($this->name . '-' . $collection . '_' . $name);
            }
        }

        if (isset($item->_initializerTrait)) {
            if (!$item->_initialized) {
                $item->init();
            }
            if (!$item->_initialized) {
                throw (new Exception('You should call parent::init() when you override initializer'))
                    ->addMoreInfo('collection', $collection)
                    ->addMoreInfo('object', $item);
            }
        }

        return $item;
    }

    /**
     * Removes element from specified collection.
     *
     * @param string $collection property name
     */
    public function _removeFromCollection(string $name, string $collection): void
    {
        if ($this->_tryGetFromCollection($name, $collection) === null) {
            throw (new Exception('Element is NOT in the collection'))
                ->addMoreInfo('collection', $collection)
                ->addMoreInfo('name', $name);
        }
        unset($this->{$collection}[$name]);
    }

    /**
     * Call this on collections after cloning object. This will clone all collection
     * elements (which are objects).
     *
     * @param string $collectionName property name to be cloned
     */
    public function _cloneCollection(string $collectionName): void
    {
        $this->{$collectionName} = array_map(function ($item) {
            $item = clone $item;
            if (isset($item->owner)) {
                $item->owner = $this;
            }

            return $item;
        }, $this->{$collectionName});
    }

    /**
     * Returns object from collection or null if object is not found.
     *
     * @param string $collection property name
     */
    public function _tryGetFromCollection(string $name, string $collection): ?object
    {
        $data = $this->{$collection};

        return $data[$name] ?? null;
    }

    /**
     * Use _tryGetFromCollection() instead and note it return null instead of false on non-match.
     *
     * @deprecated will be removed in 2021-jun
     */
    public function _hasInCollection(string $name, string $collection)
    {
        return $this->_tryGetFromCollection($name, $collection) ?? false;
    }

    /**
     * @param string $collection property name
     */
    public function _getFromCollection(string $name, string $collection): object
    {
        $item = $this->_tryGetFromCollection($name, $collection);
        if ($item === null) {
            throw (new Exception('Element is NOT in the collection'))
                ->addMoreInfo('collection', $collection)
                ->addMoreInfo('name', $name);
        }

        return $item;
    }

    /**
     * Method used internally for shortening object names
     * Identical implementation to ContainerTrait::_shorten.
     *
     * @param string $desired desired name of the object
     *
     * @return string shortened name
     */
    protected function _shorten_ml(string $desired): string
    {
        // ugly hack to deduplicate code
        $collectionTraitHelper = \Closure::bind(function () {
            $factory = Factory::getInstance();
            if (!property_exists($factory, 'collectionTraitHelper')) {
                $factory->collectionTraitHelper = new class() {
                    use AppScopeTrait;
                    use ContainerTrait;

                    public function shorten(?object $app, string $desired): string
                    {
                        $this->_appScopeTrait = $app !== null;

                        try {
                            $this->app = $app;

                            return $this->_shorten($desired);
                        } finally {
                            $this->app = null; // important for GC
                        }
                    }
                };
            }

            return $factory->collectionTraitHelper;
        }, null, Factory::class)();

        return $collectionTraitHelper->shorten($this->_appScopeTrait ? $this->app : null, $desired);
    }
}
