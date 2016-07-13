<?php

namespace atk4\core\tests;

use atk4\core;

class ContainerMock
{
    use core\NameTrait;
    use core\ContainerTrait;

    public function getElementCount()
    {
        return count($this->elements);
    }
}
