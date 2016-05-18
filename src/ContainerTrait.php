<?php

namespace atk4\core;

/**
 * This trait makes it possible for you to add child objects
 * into your object.
 */
trait ContainerTrait {

    /**
     * Check this property to see if ContainerTrait is present
     * in the object
     *
     * @var string
     */
    public $_containerTrait = true;

    /**
     * Unique object name
     *
     * @var string
     */
    public $name;

    /**
     * short_name => object hash of children objects. If the child is not
     * trackable, then object will be set to "true" (to avoid extra reference)
     *
     * @var array
     */
    protected $elements;

    private $_element_name_counts = array();
    public function _unique_element($desired)
    {
        $postfix = @++$this->_element_name_counts[$desired];

        return $desired.($postfix > 1 ? ('_'.$postfix) : '');
    }

    /**
     * If you are using ContainerTrait only, then you can safely
     * use this add() method. If you are also using factory, or
     * initializer then redefine add() and call
     * _add_Container, _add_Factory, 
     */
    function add($obj, $args = [])
    {
        if (isset($this->_factoryTrait)) {
            // Factory allows us to pass string-type objects
            $obj = $this->_add_Factory($obj, $args);
        }
        $obj = $this->_add_Container($obj, $args);

        if (isset($obj->_initializerTrait)) {
            $obj->init();
        }
        return $obj;
    }

    /**
     * Extension to add() method which will perform linking of
     * the object with the current class.
     */
    protected function _add_Container($element, $args = [])
    {
        if(!is_object($element)) {
            throw new Exception(['Only objects may be added into containers','arg'=>$element]);
        }

        // Normalize the arguments, bring name out
        if (is_string($args)) {

            // passed as string
            $args=[$args];
        } elseif (!is_array($args)) {

            throw new Exception(['Second argument must be array','arg2'=>$args]);
        } elseif (isset($args['name'])) {

            // passed as ['name'=>'foo'];
            $args[0]=$args['name'];
            unset($args['name']);
        } elseif (isset($element->short_name)) {

            // element has a name already
            $args[0]=$element->short_name;
        } elseif (isset($element->_trackableTrait)) {

            // ask element on his preferred name, then make it unique.
            $cn = $element->getDesiredName();
            $args[0] = $this->_unique_element($cn);
        } else {

            // generate name based on the class
            $cn = str_replace('\\', '_', strtolower(get_class($element)));
            $args[0] = $this->_unique_element($cn);
        }

        // Maybe element already exists
        if (isset($this->elements[$args[0]])) {
            throw new Exception([
                'Element with requested name already exists',
                'element'=>$element,
                'name'=>$args[0],
                'this'=>$this,
                'arg2'=>$args
            ]);
        }

        if(isset($this->_appScopeTrait) && isset($element->_appScopeTrait)) {
            $element->app = $this->app;
        }

        $element->owner = $this;
        $element->short_name = $args[0];


        if(isset($element->_trackableTrait)) {

            $element->name = $this->_shorten($this->name.'_'.$element->short_name);

            $this->elements[$element->short_name] = $element;
        } else {
            // dont store extra reference to models and controlers
            // for purposes of better garbage collection.
            $this->elements[$element->short_name] = true;
        }
        
        return $element;
    }

    /**
     * Remove child element if it exists.
     *
     * @param string $short_name short name of the element
     *
     * @return $this
     */
    public function removeElement($short_name)
    {

        if (is_object($short_name)) {
            $short_name = $short_name->short_name;
        }
        unset($this->elements[$short_name]);
        return $this;
    }

    /**
     * Method used internally for shortening object names.
     *
     * @param string $desired Desired name of new object.
     *
     * @return string Shortened name of new object.
     */
    protected function _shorten ($desired) {
        if (
            isset($this->_appScopeTrait) &&
            isset($this->app->max_name_length) &&
            strlen($desired) > $this->app->max_name_length
        ) {
            // $len is the amount to chomp. It must divide
            // by max_name_length, so that we keep chomping
            // by consistent amounts
            $len = strlen($desired)-15;
            $len -= ($len % $this->app->max_name_length)+5;

            $key = substr($desired, 0, $len);
            $rest = substr($desired, $len);

            if (!isset($this->app->unique_hashes[$key])) {
                $this->app->unique_hashes[$key] = dechex(crc32($key));
            }
            $desired = $this->app->unique_hashes[$key].'__'.$rest;
        };

        return $desired;
    }

    /**
     * Find child element by its short name. Use in chaining.
     * Exception if not found.
     *
     * @param string $short_name Short name of the child element
     *
     * @return AbstractObject
     */
    function getElement($short_name) {
        if (!isset($this->elements[$short_name])) {
            throw new Exception([
                'Child element not found',
                'element'=>$short_name
            ]);
        }

        return $this->elements[$short_name];
    }

    /**
     * Find child element. Use in condition.
     *
     * @param string $short_name Short name of the child element
     *
     * @return AbstractObject|bool
     */
    public function hasElement($short_name)
    {
        return isset($this->elements[$short_name])
            ? $this->elements[$short_name]
            : false;
    }
}
