<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\TranslatableTrait;

class TranslatorAdapterAppTest extends TranslatorAdapterBase
{
    public function getTranslatableMock()
    {
        $app = new class() {
            use TranslatableTrait;
        };

        $mock = new class() {
            use AppScopeTrait;
            use TranslatableTrait;
        };

        $mock->setApp($app);

        return $mock;
    }
}
