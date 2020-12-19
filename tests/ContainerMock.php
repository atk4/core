<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;

class ContainerMock
{
    use core\ContainerTrait;
    use core\NameTrait;

    public function getElementCount()
    {
        return count($this->elements);
    }
}
