<?php

return  [
    'atk4' => [
        'string without counter'       => ['frase senza contatore'],
        'string not translated simple' => [
            1 => 'frase semplice',
        ],
        'string not translated with plurals' => [
            0 => 'frase forma plurale zero',
            1 => 'frase forma plurale uno',
            2 => 'frase forma plurale due',
        ],
        'no-counter: %s, zero: %s, singular : %s, plural : %s'  => 'no-plurale: %s, forma zero: %s, forma singolare : %s, forma plurale : %s'
    ],
    'other-domain' => [
        'string without counter'    => ['altro dominio stessa stringa'],
        'string not translated simple' => [
            1 => 'altro dominio frase semplice',
        ],
        'string not translated with plurals' => [
            0 => 'altro dominio frase forma plurale zero',
            1 => 'altro dominio frase forma plurale uno',
            2 => 'altro dominio frase forma plurale due',
        ],
        'no-counter: %s, zero: %s, singular : %s, plural : %s'  => 'altro dominio | no-plurale: %s, forma zero: %s, forma singolare : %s, forma plurale : %s'
     ]
];
