<?php

use Dehare\SCPHP\Command\Command;

return [
    'status'  => [
        '_command' => 'serverstatus',
        'limit'    => 5,
        'query'    => Command::QUERY_ARRAY,
        'flags'    => [\Dehare\SCPHP\API::FLAG_UNWRAP],
    ],
    'secured' => [
        '_command' => 'pref authorize ?',
        'query'    => Command::QUERY_BOOL,
    ],
    'login' => [
        'parameters' => [
            'username' => null,
            'password' => null,
        ],
        'requirements' => ['username', 'password'],
        'filters' => [\Dehare\SCPHP\API::FILTER_KEYLESS],
    ],
];