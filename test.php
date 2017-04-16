<?php
require_once  'vendor/autoload.php';


use Dehare\SCPHP;

$conn = new SCPHP\Connection('192.168.1.50');

// concept testing
$status = explode(' ', SCPHP\Request::command('serverstatus 0 10'));
$result = [];
array_walk($status, function($v) use (&$result) {
    $v = urldecode($v);
    $result[strstr($v, ':', true)] = ltrim(strstr($v, ':'), ':');
});

var_dump($result);

// concept proof
var_dump(SCPHP\Request::query('database:albums', [], []));

// fill empty tags with null data
var_dump(SCPHP\Request::query('database:albums', [], ['fill_tags' => true]));