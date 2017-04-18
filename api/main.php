<?php

use Dehare\SCPHP\Command\Command;

return [
    'status'  => [
        'command' => 'serverstatus',
        'limit'   => 5,
        'query'   => Command::QUERY_ARRAY,
    ],
    'secured' => [
        'command' => 'pref authorize ?',
        'query'   => Command::QUERY_BOOL,
    ],
];