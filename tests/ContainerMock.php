<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\ContainerTrait;
use Atk4\Core\NameTrait;

class ContainerMock
{
    use ContainerTrait;
    use NameTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }
}
