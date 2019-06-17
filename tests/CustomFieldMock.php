<?php

namespace atk4\core\tests;

use atk4\core\InitializerTrait;

class CustomFieldMock extends FieldMock
{
    use InitializerTrait {
        init as _init;
    }

    /** @var null verifying if init wal called */
    public $var = null;

    public function init()
    {
        $this->_init();

        $this->var = true;
    }
}
