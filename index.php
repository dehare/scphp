<?php
require_once  'vendor/autoload.php';


use Dehare\SCPHP\Request;
use Dehare\SCPHP\Connection;

Connection::setPort(9999);
Connection::connect();

// concept testing
var_dump(Request::query('status'));

// concept proof
var_dump(Request::query('database:albums', [], [\Dehare\SCPHP\API::FLAG_COUNT_ONLY => true]));

// fill empty tags with null data
var_dump(Request::query('database:albums', [], ['fill_tags' => true]));
var_dump(Request::query('database:years'));
var_dump(Request::query('database:info:albums'));
var_dump(Request::query('database:info:songs'));