<?php

namespace atk4\core;

trait ContainerTrait {

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
     * Extension to add() method which will perform container tracking
     */
    protected function _add_Container() {
    }

    protected function _shorten () {
    }

    function getElement() {
    }

    function hasElement() {
    }

}
