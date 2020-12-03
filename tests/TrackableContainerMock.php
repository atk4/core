<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;

class TrackableContainerMock
{
    use core\ContainerTrait;
    use core\TrackableTrait;

    public function getElementCount()
    {
        return count($this->elements);
    }
}
