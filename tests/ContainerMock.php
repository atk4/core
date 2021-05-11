<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;

class ContainerMock
{
    use Core\ContainerTrait;
    use Core\NameTrait;

    public function getElementCount(): int
    {
        return count($this->elements);
    }
}
