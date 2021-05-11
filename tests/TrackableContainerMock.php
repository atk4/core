<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;

class TrackableContainerMock
{
    use Core\ContainerTrait;
    use Core\TrackableTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }
}
