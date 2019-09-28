<?php

namespace atk4\core\tests;

use atk4\core\TranslatableTrait;
use atk4\core\Translator\Translator;
use atk4\data\Persistence;
use PHPUnit\Framework\TestCase;

class TranslatableTraitTest extends TestCase
{
    public function getMock()
    {
        return new class() {
            use TranslatableTrait;
        };
    }

    public function testTranslatableTrait()
    {
        $trans = Translator::instance();
        $trans->setDefaultLocale('ru');

        try {
            Persistence::connect('error:error');
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }
}
