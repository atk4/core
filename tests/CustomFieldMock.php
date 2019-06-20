<?php

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
    public $var = null;

    public function init()
    {
        $this->_init();

        $this->var = true;
    }
}
