<?php

namespace atk4\core\tests;

use atk4\core;

class TrackableContainerMock
{
    use core\ContainerTrait;
    use core\TrackableTrait;

    public function getElementCount()
    {
        return count($this->elements);
    }
}
