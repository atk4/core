<?php

namespace atk4\core\tests;

use atk4\core\TranslatableTrait;
use atk4\core\Translator;
use Symfony\Component\Console\Application;

class TranslatableMock
{
    use TranslatableTrait;

    public $app;

    private $translation = [
        'string without counter'       => 'string without counter translated',
        'string not translated simple' => [
            1 => 'string translated',
        ],
        'string not translated with plurals' => [
            0 => 'string translated zero',
            1 => 'string translated singular',
            2 => 'string translated plural',
        ],
        'string with exception'                            => [],
        'single: %s, zero: %s, singular : %s, plural : %s' => 'translated : zero: %s, singular : %s, plural : %s',
    ];

    public function __construct()
    {
        $this->app = new class {

            public $translator;

            public function __construct()
            {
                $this->translator = new Translator();
            }

            public function _(string $message, int $count = 1, string $context = 'atk4'): string
            {
                return $this->translator->translate($message, $count, $context);
            }
        };

        foreach ($this->translation as $key => $args) {
            if (!is_array($args)) {
                $args = [$args];
            }

            $this->app->translator->addOne($key, $args);
        }
    }
}
