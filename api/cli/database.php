<?php

use Dehare\SCPHP\Command\Command;

/**
 * Parameters: key / default value
 * Tags: letter / return key
 */

$cmd = [
    'info'     => [
        'query'   => Command::QUERY_INT,
        'prefix'  => 'total',
        'options' => ['genres', 'artists', 'albums', 'songs', 'duration'],
    ],
    'genres'   => [
        'query'      => Command::QUERY_ARRAY,
        'limit'      => 50,
        'parameters' => [
            'search'    => null,
            'artist_id' => null,
            'album_id'  => null,
            'track_id'  => null, // ignores other filters
            'genre_id'  => null, // ignores other filters
            'year'      => null,
        ],
        'tags'       => [
            's' => 'textkey',
            //'s' => ['textkey', '.{1}'],
            '_' => null,
        ],
        'response'   => ['id', 'genre'],
    ],
    'artists'  => [
        'query'      => Command::QUERY_ARRAY,
        'limit'      => 50,
        'parameters' => [
            'search'    => null,
            'artist_id' => null, // ignores other filters
            'album_id'  => null,
            'track_id'  => null, // ignores other filters
            'genre_id'  => null,
        ],
        'tags'       => [
            's' => ['textkey', '.{1}'],
            '_' => null,
        ],
        'response'   => ['id', 'artist'],
    ],
    'albums'   => [
        'query'      => Command::QUERY_ARRAY,
        'limit'      => 25,
        'parameters' => [
            'search'      => null,
            'artist_id'   => null,
            'album_id'    => null,  // ignores other filters
            'track_id'    => null, // ignores other filters
            'genre_id'    => null,
            'year'        => null,
            'compilation' => null,
            'sort'        => [
                'options' => ['album', 'new', 'artflow'],
                '_'       => 'album',
            ],
        ],
        'tags'       => [
            'l' => 'album',
            //'l' => ['album', '.+'],
            'y' => 'year',
            //'y' => ['year', '\d+'],
            'j' => 'artwork_track_id',
            //'j' => ['artwork_track_id', '\w+'],
            't' => 'title',
            //'t' => ['title', '.+'],
            'i' => 'disc',
            //'i' => ['disc', '\d+'],
            'q' => 'disccount',
            //'q' => ['disccount', '\d+'],
            'w' => 'compilation',
            //'w' => ['compilation', '\d'],
            'a' => 'artist',
            //'a' => ['artist', '.+'],
            'S' => 'artist_id',
            //'S' => ['artist_id', '\d+'],
            's' => 'textkey',
            //'s' => ['textkey', '.{1}'],
            'X' => 'album_replay_gain',
            //'X' => ['album_replay_gain', '\d+'],
            '_' => '*',
        ],
        'response'   => ['id', 'album'],
    ],
    'years'    => [
        'query'      => Command::QUERY_ARRAY,
        'limit'      => 100,
        'parameters' => [
            'hasAlbums' => 1,
        ],
        'response'   => ['year'],
        'flags'    => [\Dehare\SCPHP\API::FLAG_UNWRAP_KEYS],
    ],
    'songinfo' => [
        'query'      => Command::QUERY_ARRAY,
        'parameters' => [
            'url'      => null,
            'track_id' => null,
        ],
        'tags'       => [
            'a' => 'artist',
            'A' => '_array_',
            'B' => 'buttons',
            'c' => 'coverid',
            'C' => 'compilation',
            'd' => 'duration',
            'e' => 'album_id',
            'f' => 'filesize',
            'g' => 'genre',
            'G' => 'genres',
            'i' => 'disc',
            'I' => 'samplesize',
            'j' => 'coverart',
            'J' => 'artwork_track_id',
            'k' => 'comment',
            'K' => 'artwork_url',
            'l' => 'album',
            'L' => 'info_link',
            'm' => 'bpm',
            'M' => 'musicmagic_mixable',
            'n' => 'modificationTime',
            'N' => 'remote_title',
            'o' => 'type',
            'p' => 'genre_id',
            'P' => 'genre_ids',
            'D' => 'addedTime',
            'U' => 'lastUpdated',
            'q' => 'disccount',
            'r' => 'bitrate',
            'R' => 'rating',
            's' => 'artist_id',
            'S' => '_array_',
            't' => 'tracknum',
            'T' => 'samplerate',
            'u' => 'url',
            'v' => 'tagversion',
            'w' => 'lyrics',
            'x' => 'remote',
            'X' => 'album_replay_gain',
            'y' => 'year',
            'Y' => 'replay_gain',
        ],
        'response'   => ['id', 'title'],
    ],
    'titles'   => [
        'query'      => Command::QUERY_ARRAY,
        'parameters' => [
            'search'    => null,
            'genre_id'  => null,
            'artist_id' => null,
            'album_id'  => null,
            'track_id'  => null,
            'year'      => null,
            'sort'      => [
                'options' => ['title', 'tracknum', 'albumtrack'],
                '_'       => 'title',
            ],
        ],
        'response'   => [],
    ],
];

$cmd['titles']['tags'] = $cmd['songinfo']['tags'];
$cmd['tracks']         = $cmd['songs'] = $cmd['titles'];

return $cmd;