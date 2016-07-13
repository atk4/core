<?php

namespace atk4\core;

/**
 * This trait makes it possible to set name of your object.
 */
trait ObjectTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_objectTrait = true;

    /**
     * Unique object name.
     *
     * @var string
     */
    public $name;
}
