<?php

namespace atk4\core\tests;

use atk4\core\TranslatableTrait;

class TranslatorAdapterGenericTest extends TranslatorAdapterBase
{
    public function getTranslatableMock()
    {
        return new class() {
            use TranslatableTrait;
        };
    }
}
