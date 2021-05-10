<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\DiContainerTrait;

class FieldMock
{
    use DiContainerTrait;

    /** @var string */
    public $name;
}
