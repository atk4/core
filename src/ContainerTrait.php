<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * This trait makes it possible for you to add child objects
 * into your object.
 */
trait ContainerTrait
{
    /**
     * Element shortName => object hash of children objects. If the child is not
     * trackable, then object will be set to "true" (to avoid extra reference).
     *
     * @var array<string, object>
     */
    public array $elements = [];

    /** @var array<string, int> */
    private array $_elementNameCounts = [];

    /**
     * Returns unique element name based on desired name.
     */
    protected function _uniqueElementName(string $desired): string
    {
        if (!isset($this->_elementNameCounts[$desired])) {
            $this->_elementNameCounts[$desired] = 1;
            $postfix = '';
        } else {
            $postfix = '_' . (++$this->_elementNameCounts[$desired]);
        }

        return $desired . $postfix;
    }

    /**
     * If you are using ContainerTrait only, then you can safely
     * use this add() method. If you are also using factory, or
     * initializer then redefine add() and call _addContainer, _addFactory.
     *
     * @param object|array<mixed, mixed> $obj
     * @param array<mixed, mixed>|string $args
     */
    public function add($obj, $args = []): object
    {
        $obj = Factory::factory($obj, is_string($args) ? [] : array_diff_key($args, [true, 'desired_name' => true]));

        $this->_addContainer($obj, is_string($args) ? ['name' => $args] : $args);

        if (TraitUtil::hasInitializerTrait($obj)) {
            if (!$obj->isInitialized()) {
                try {
                    $obj->invokeInit();
                } catch (\Throwable $e) {
                    if (TraitUtil::hasTrackableTrait($obj)) {
                        unset($this->elements[$obj->shortName]);
                    }

                    throw $e;
                }
            }
        }

        return $obj;
    }

    /**
     * Extension to add() method which will perform linking of
     * the object with the current class.
     *
     * @param array{desired_name?: string, name?: string} $args
     */
    protected function _addContainer(object $element, array $args): void
    {
        // carry on reference to application if we have appScopeTraits set
        if (TraitUtil::hasAppScopeTrait($this) && TraitUtil::hasAppScopeTrait($element)
            && (!$element->issetApp() || $element->getApp() !== $this->getApp())
        ) {
            $element->setApp($this->getApp());
        }

        // if element is not trackable, then we don't need to do anything with it
        if (!TraitUtil::hasTrackableTrait($element)) {
            return;
        }

        // normalize the arguments, bring name out
        if (isset($args['desired_name'])) {
            $name = $this->_uniqueElementName($args['desired_name']);
            unset($args['desired_name']);
        } elseif (isset($args['name'])) {
            $name = $args['name'];
            unset($args['name']);
        } elseif (($element->shortName ?? null) !== null) {
            $name = $this->_uniqueElementName($element->shortName);
        } else {
            $desiredName = $element->getDesiredName();
            $name = $this->_uniqueElementName($desiredName);
        }

        if ($args !== []) {
            throw (new Exception('Add args for DI are no longer supported'))
                ->addMoreInfo('arg', $args);
        }

        // maybe element already exists
        if (isset($this->elements[$name])) {
            throw (new Exception('Element with requested name already exists'))
                ->addMoreInfo('element', $element)
                ->addMoreInfo('name', $name)
                ->addMoreInfo('this', $this)
                ->addMoreInfo('arg2', $args);
        }

        $element->setOwner($this);
        $element->shortName = $name;
        if (TraitUtil::hasTrackableTrait($this) && TraitUtil::hasNameTrait($this) && TraitUtil::hasNameTrait($element)) {
            $element->name = $this->_shorten($this->name ?? '', $element->shortName, $element->name ?? null); // @phpstan-ignore property.notFound
        }

        $this->elements[$element->shortName] = $element;
    }

    /**
     * Remove child element if it exists.
     *
     * @param string|object $shortName short name of the element
     *
     * @return $this
     */
    public function removeElement($shortName)
    {
        if (is_object($shortName)) {
            $shortName = $shortName->shortName;
        }

        if (!isset($this->elements[$shortName])) {
            throw (new Exception('Child element not found'))
                ->addMoreInfo('parent', $this)
                ->addMoreInfo('name', $shortName);
        }

        unset($this->elements[$shortName]);

        return $this;
    }

    /**
     * Method used internally for shortening object names.
     */
    protected function _shorten(string $ownerName, string $itemShortName, ?string $origItemName): string
    {
        $desired = $origItemName ?? $ownerName . '_' . $itemShortName;

        if (TraitUtil::hasAppScopeTrait($this)
            && isset($this->getApp()->maxNameLength)
            && mb_strlen($desired) > $this->getApp()->maxNameLength
        ) {
            if ($origItemName !== null) {
                throw (new Exception('Element has too long desired name'))
                    ->addMoreInfo('name', $origItemName);
            }

            $left = mb_strlen($desired) + 35 - $this->getApp()->maxNameLength;
            $key = mb_substr($desired, 0, $left);
            $rest = mb_substr($desired, $left);

            if (!isset($this->getApp()->uniqueNameHashes[$key])) {
                $this->getApp()->uniqueNameHashes[$key] = '_' . md5($key);
            }
            $desired = $this->getApp()->uniqueNameHashes[$key] . '__' . $rest;
        }

        return $desired;
    }

    /**
     * Find child element by its short name. Use in chaining.
     * Exception if not found.
     *
     * @param string $shortName Short name of the child element
     */
    public function getElement(string $shortName): object
    {
        if (!isset($this->elements[$shortName])) {
            throw (new Exception('Child element not found'))
                ->addMoreInfo('parent', $this)
                ->addMoreInfo('element', $shortName);
        }

        return $this->elements[$shortName];
    }

    /**
     * @param string $shortName Short name of the child element
     */
    public function hasElement($shortName): bool
    {
        return isset($this->elements[$shortName]);
    }
}
