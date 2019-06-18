<?php

return  [
    'atk4' => [
        'string without counter'       => ['string without counter translated'],
        'string not translated simple' => [
            1 => 'string translated',
        ],
        'string not translated with plurals' => [
            0 => 'string translated zero',
            1 => 'string translated singular',
            2 => 'string translated plural',
        ],
        'string with exception array empty'                            => [],
        'string fallback test'  => 'fallback to en',
        'no-counter: %s, zero: %s, singular : %s, plural : %s'  => 'translated - no-counter: %s, zero: %s, singular : %s, plural : %s'
    ],
    'other-domain' => [
        'string without counter'    => ['other domain string without counter translated']
    ]
];