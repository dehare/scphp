<?php


namespace Dehare\SCPHP\Parser\Result;


class ArrayResult implements ResultInterface
{
    private $data  = [];
    private $count = -1;

    public function __construct($data, $count = -1)
    {
        $this->data = $data;
        if (is_numeric($count) && $count > -1) {
            $this->count = intval($count);
        }

    }

    public function getData()
    {
        return $this->data;
    }


    public function getCount()
    {
        return $this->count;
    }

    public function hasData()
    {
        return count($this->data) > 0;
    }

    /**
     * Fills missing keys with nullable data
     *
     * @param array $keys All keys to be filled
     *
     * @see API::FLAG_FILL_KEYS
     */
    public function fillKeys(array $keys)
    {
        $nulls = array_combine($keys, array_fill(0, count($keys), null));

        foreach ($this->data as $k => &$data) {
            if (! is_array($data)) {
                continue;
            }
            $data = array_replace($nulls, $data);
        }
    }

    /**
     * Unwrap results if flag is provided
     *
     * @see API::FLAG_UNWRAP
     */
    public function unwrap()
    {
        if (count($this->data) == 1) {
            $this->data = $this->data[0];
        }
    }

    /**
     * Unwrap result subsets if flag is provided
     *
     * @param string $key Key to grab per record
     *
     * @see API::FLAG_UNWRAP_KEYS
     */
    public function unwrapKeys($key)
    {
        $this->data = array_column($this->data, $key);
    }
}