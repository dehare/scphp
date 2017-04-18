<?php
require_once  'vendor/autoload.php';


use Dehare\SCPHP\Request;

$conn = new \Dehare\SCPHP\Connection('192.168.1.50');

// concept testing
$status = Request::query('status');
var_dump($status);

// concept proof
var_dump(Request::query('database:albums', [], []));

// fill empty tags with null data
var_dump(Request::query('database:albums', [], ['fill_tags' => true]));