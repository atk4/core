<?php

declare(strict_types=1);

namespace Atk4\Core;

/**
 * This trait makes it possible to set name of your object.
 */
trait NameTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_nameTrait = true;

    /**
     * Unique object name.
     *
     * @var string
     */
    public $name;
}
