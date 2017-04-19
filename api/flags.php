<?php

use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\API;

return [
    API::FLAG_FILL_KEYS   => [
        'query'       => [Command::QUERY_ARRAY],
        'description' => 'Fills unreturned keys with nullable data',
    ],
    API::FLAG_UNWRAP      => [
        'query'       => [Command::QUERY_ARRAY],
        'description' => 'Return data without results wrapper',
    ],
    API::FLAG_COUNT_ONLY  => [
        'query'       => [Command::QUERY_ARRAY],
        'description' => 'Returns count of CLI result set, NOT query result',
    ],
    API::FLAG_RAW         => [
        'query'       => [Command::QUERY_ARRAY, Command::QUERY_INT, Command::QUERY_SUCCESS, Command::QUERY_STRING, Command::QUERY_BOOL],
        'description' => 'Return unprocessed data',
    ],
    API::FLAG_UNWRAP_KEYS => [
        'query'       => [Command::QUERY_ARRAY],
        'description' => 'Return data only when only one key exists in data',
    ],
];