<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\TranslatableTrait;

class TranslatorAdapterAppTest extends TranslatorAdapterBase
{
    public function getTranslatableMock(): object
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
