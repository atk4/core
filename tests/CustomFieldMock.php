<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\InitializerTrait;
use atk4\core\TrackableTrait;

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
