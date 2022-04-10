<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\InitializerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\TrackableTrait;

class FieldMockCustom extends FieldMock
{
    use AppScopeTrait;
    use InitializerTrait {
        init as private _init;
    }
    use NameTrait;
    use TrackableTrait;

    /** @var bool verifying if init was called */
    public $var;

    protected function init(): void
    {
        $this->_init();

        $this->var = true;
    }
}
