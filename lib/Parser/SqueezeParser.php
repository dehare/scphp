<?php

namespace Dehare\SCPHP\Parser;

use Dehare\SCPHP\API;
use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Parser\Result\ResultInterface;

abstract class SqueezeParser implements ParserInterface
{
    /** @var Command */
    protected $command;
    /** @var string */
    protected $data;
    /** @var array */
    protected $flags = [];
    /**
     * @var array
     * @todo replace with ParameterBag
     */
    protected $options = [];

    /** @var null|ResultInterface */
    protected $result = null;

    /**
     * SqueezeParser constructor.
     *
     * @param Command $command
     * @param array   $flags
     */
    public function __construct(Command $command, array $flags = [], array $options = [])
    {
        $this->command = $command;
        $this->flags   = $flags;
        $this->options = new \ArrayObject($options);
    }

    /**
     * Wrapper for handling and parsing data into result
     *
     * @param string $data
     *
     * @return ResultInterface
     */
    public function parse($data)
    {
        $this->setData($data);
        $this->setResult();

        return $this->getResult();
    }

    /**
     * @return ResultInterface
     */
    abstract protected function getResult();

    /**
     * Set ResultInterface
     */
    abstract protected function setResult();

    /**
     * Set data
     *
     * @param string $data
     */
    protected function setData($data)
    {
        if (! is_string($data)) { // todo preg_match
            throw new \InvalidArgumentException('Expecting raw query result, got ' . gettype($data));
        }

        $this->data = $data;
    }

    protected function splitByDelimiter($delimiter)
    {
        $results = [];

        $this->data = $delimiter ? array_filter(explode(' ' . $delimiter, ' ' . $this->data)) : (array)$this->data;
        foreach ($this->data as $d) {
            $line       = [];
            $columns    = explode(' ', $d);
            $columns[0] = $delimiter . $columns[0];

            foreach ($columns as $col) {
                $col                           = urldecode($col);
                $line[strstr($col, ':', true)] = ltrim(strstr($col, ':'), ':');
            }

            $line = array_filter($line, function ($v) {
                return (is_array($v) && ! empty($v)) || $v != ''; // no empty arrays, no empty strings
            });

            if (! empty($line)) {
                $results[] = $line;
            }
        }

        return $results;
    }

    protected function getCountFromData()
    {
        $count = -1;
        if (preg_match('/count%3A(\d+)$/', $this->data, $m)) {
            $count = $m[1];
        }

        return intval($count);
    }

    /**
     * Get count from result set and unset the key
     *
     * @param array $result
     *
     * @return int
     */
    protected function stripCountFromResult(array &$result)
    {
        $count = -1;

        $rc = count($result); // results count
        if (! empty($result) && isset($result[$rc - 1]['count'])) {
            $count = $result[$rc - 1]['count'];
            unset($result[$rc - 1]['count']);
        }

        return $count;
    }

    /**
     * Check if flag is supplied
     *
     * @param string $flag One of API::FLAG_*
     *
     * @return bool
     * @see Api::getFlags()
     */
    protected function hasFlag($flag)
    {
        return in_array($flag, $this->flags);
    }
}