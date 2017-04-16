<?php

namespace Dehare\SCPHP;

class Database
{
    public function query($key, $filters = [], $options = []) {
        Request::setCommands('database');
        return Request::query($key);
    }
}