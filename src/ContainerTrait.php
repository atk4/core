<?php

namespace atk4\core;

trait ContainerTrait {

    public $name;

    public $_containerTrait = true;

    protected $elements;

    private $_element_name_counts = array();
    public function _unique_element($desired = null)
    {
        $postfix = @++$this->_element_name_counts[$desired];

        return $desired.($postfix > 1 ? ('_'.$postfix) : '');
    }

    protected function _unique() {
    }

    /**
     * Extension to add() method which will perform linking of
     * the object with the current class.
     */
    protected function _add_Container($element, $args = [])
    {
        // Normalize the arguments, bring name out
        if (is_string($args)) {

            // passed as string
            $args=[$args];
        } elseif (!is_array($args)) {

            throw new Exception(['Second argument must be array','arg2'=>$args]);
        } elseif (is_array($args) && isset($args['name'])) {

            // passed as ['name'=>'foo'];
            $args[0]=$args['name'];
            unset($args['name']);
        } elseif (isset($element->short_name)) {

            // element has a name already
            $args[0]=$element->short_name;
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

        $element->owner = $this;
        $element->short_name = $args[0];
        $element->name = $this->_shorten($this->name.'_'.$element->short_name);

        if(isset($element->_trackableTrait)) {
            $this->elements[$element->short_name] = $element;
        } else {
            // dont store extra reference to models and controlers
            // for purposes of better garbage collection
            $this->elements[$element->short_name] = true;
        }
        
        return $element;
    }

    protected function _shorten () {
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
