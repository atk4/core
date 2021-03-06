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
     * short_name => object hash of children objects. If the child is not
     * trackable, then object will be set to "true" (to avoid extra reference).
     *
     * @var array
     */
    public $elements = [];

    /**
     * @var int[]
     */
    private $_element_name_counts = [];

    /**
     * Returns unique element name based on desired name.
     */
    public function _unique_element(string $desired): string
    {
        if (!isset($this->_element_name_counts[$desired])) {
            $this->_element_name_counts[$desired] = 1;
            $postfix = '';
        } else {
            $postfix = '_' . (++$this->_element_name_counts[$desired]);
        }

        return $desired . $postfix;
    }

    /**
     * If you are using ContainerTrait only, then you can safely
     * use this add() method. If you are also using factory, or
     * initializer then redefine add() and call
     * _add_Container, _add_Factory,.
     *
     * @param mixed        $obj
     * @param array|string $args
     */
    public function add($obj, $args = []): object
    {
        if (is_array($args)) {
            $args1 = $args;
            unset($args1['desired_name']);
            unset($args1[0]);
            $obj = Factory::factory($obj, $args1);
        } else {
            $obj = Factory::factory($obj);
        }
        $obj = $this->_add_Container($obj, $args);

        if (TraitUtil::hasInitializerTrait($obj)) {
            if (!$obj->_initialized) {
                $obj->invokeInit();
            }
            if (!$obj->_initialized) {
                throw (new Exception('You should call parent::init() when you override initializer'))
                    ->addMoreInfo('obj', $obj);
            }
        }

        return $obj;
    }

    /**
     * Extension to add() method which will perform linking of
     * the object with the current class.
     *
     * @param array|string $args
     */
    protected function _add_Container(object $element, $args = []): object
    {
        // Carry on reference to application if we have appScopeTraits set
        if (TraitUtil::hasAppScopeTrait($this) && TraitUtil::hasAppScopeTrait($element)) {
            $element->setApp($this->getApp());
        }

        // If element is not trackable, then we don't need to do anything with it
        if (!TraitUtil::hasTrackableTrait($element)) {
            return $element;
        }

        // Normalize the arguments, bring name out
        if (is_string($args)) {
            // passed as string
            $args = [$args];
        } elseif (!is_array($args) && $args !== null) {
            throw (new Exception('Second argument must be array'))
                ->addMoreInfo('arg2', $args);
        } elseif (isset($args['desired_name'])) {
            // passed as ['desired_name'=>'foo'];
            $args[0] = $this->_unique_element($args['desired_name']);
            unset($args['desired_name']);
        } elseif (isset($args['name'])) {
            // passed as ['name'=>'foo'];
            $args[0] = $args['name'];
            unset($args['name']);
        } elseif (isset($element->short_name)) {
            // element has a name already
            $args[0] = $this->_unique_element($element->short_name);
        } else {
            // ask element on his preferred name, then make it unique.
            $cn = $element->getDesiredName();
            $args[0] = $this->_unique_element($cn);
        }

        // Maybe element already exists
        if (isset($this->elements[$args[0]])) {
            throw (new Exception('Element with requested name already exists'))
                ->addMoreInfo('element', $element)
                ->addMoreInfo('name', $args[0])
                ->addMoreInfo('this', $this)
                ->addMoreInfo('arg2', $args);
        }

        $element->setOwner($this);
        $element->short_name = $args[0];
        if (TraitUtil::hasNameTrait($this)) {
            $element->name = $this->_shorten($this->name . '_' . $element->short_name);
        }
        $this->elements[$element->short_name] = $element;

        unset($args[0]);
        unset($args['name']);
        foreach ($args as $key => $arg) {
            if ($arg !== null) {
                $element->{$key} = $arg;
            }
        }

        return $element;
    }

    /**
     * Remove child element if it exists.
     *
     * @param string|object $short_name short name of the element
     *
     * @return $this
     */
    public function removeElement($short_name)
    {
        if (is_object($short_name)) {
            $short_name = $short_name->short_name;
        }

        if (!isset($this->elements[$short_name])) {
            throw (new Exception('Could not remove child from parent. Instead of destroy() try using removeField / removeColumn / ..'))
                ->addMoreInfo('parent', $this)
                ->addMoreInfo('name', $short_name);
        }

        unset($this->elements[$short_name]);

        return $this;
    }

    /**
     * Method used internally for shortening object names.
     *
     * @param string $desired desired name of new object
     *
     * @return string shortened name of new object
     */
    protected function _shorten(string $desired): string
    {
        if (
            TraitUtil::hasAppScopeTrait($this)
            && isset($this->getApp()->max_name_length)
            && mb_strlen($desired) > $this->getApp()->max_name_length
        ) {
            $left = mb_strlen($desired) + 35 - $this->getApp()->max_name_length;
            $key = mb_substr($desired, 0, $left);
            $rest = mb_substr($desired, $left);

            if (!isset($this->getApp()->unique_hashes[$key])) {
                $this->getApp()->unique_hashes[$key] = '_' . md5($key);
            }
            $desired = $this->getApp()->unique_hashes[$key] . '__' . $rest;
        }

        return $desired;
    }

    /**
     * Find child element by its short name. Use in chaining.
     * Exception if not found.
     *
     * @param string $short_name Short name of the child element
     */
    public function getElement(string $short_name): object
    {
        if (!isset($this->elements[$short_name])) {
            throw (new Exception('Child element not found'))
                ->addMoreInfo('parent', $this)
                ->addMoreInfo('element', $short_name);
        }

        return $this->elements[$short_name];
    }

    /**
     * @param string $short_name Short name of the child element
     */
    public function hasElement($short_name): bool
    {
        return isset($this->elements[$short_name]);
    }
}
