<?php

namespace Dehare\SCPHP\Parser\Result;

interface ResultInterface
{
    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return int
     */
    public function getCount();

    /**
     * @return bool
     */
    public function hasData();
}