<?php

declare(strict_types=1);

namespace atk4\core\Tests;

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
