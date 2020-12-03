<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\InitializerTrait;
use Atk4\Core\TrackableTrait;

class CustomFieldMock extends FieldMock
{
    use InitializerTrait {
        init as _init;
    }

    use TrackableTrait;
    use AppScopeTrait;

    /** @var null verifying if init wal called */
    public $var;

    protected function init(): void
    {
        $this->_init();

        $this->var = true;
    }
}
