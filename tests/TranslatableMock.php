<?php

namespace atk4\core\tests;

use atk4\core\Exception;
use atk4\core\TranslatableTrait;
use atk4\core\Translator;
use atk4\core\TranslatorInterface;

class TranslatableMock
{
    use TranslatableTrait;

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

    /**
     * @Given /^I am on "testTranslatable"/
     */
    public function __construct()
    {
        $this->translator = new Translator();

        foreach ($this->translation as $key => $args)  {
            if (!is_array($args)) {
                $args = [$args];
            }

            $this->translator->addOne($key, $args);
        }
    }
}