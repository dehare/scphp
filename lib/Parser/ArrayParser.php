<?php


namespace Dehare\SCPHP\Parser;


use Dehare\SCPHP\API;
use Dehare\SCPHP\Command\Command;

class ArrayParser
{
    private $data;
    private $flags  = [];

    /**
     * @var Command
     */
    private $command;

    public function __construct(Command $command, array $flags = [])
    {
        $this->command = $command;
        $this->flags   = $flags;
    }

    /**
     * Parse result to array
     *
     * @param string $data
     * @param array  $flags Data transformers
     *
     * @return array
     */
    public function parse($data)
    {
        $this->data = $data;

        if ($this->hasFlag(API::FLAG_COUNT_ONLY)) {
            return ['count' => $this->getCountFromData()];
        }

        $results = $this->splitByDelimiter($this->command->getDelimiter());
        $count   = $this->stripCountFromResult($results);
        $results = array_filter($results);

        $this->unwrapKeys($results);

        $result = compact('results', 'count');
        if ($this->hasFlag(API::FLAG_UNWRAP)) {
            $this->unwrap($results);
            $result = $results;
        }

        return $result;
    }

    protected function splitByDelimiter($delimiter, $check_flag = true)
    {
        $results = [];

        $this->data = $delimiter ? array_filter(explode(' ' . $delimiter, ' ' . $this->data)) : (array)$this->data;
        foreach ($this->data as $d) {
            $line       = [];
            $columns    = explode(' ', $d);
            $columns[0] = $delimiter . $columns[0];

            array_walk($columns, function ($v) use (&$line) {
                $v                           = urldecode($v);
                $line[strstr($v, ':', true)] = ltrim(strstr($v, ':'), ':');
            });

            $this->fillKeys($line, $check_flag);

            $line = array_filter($line, function ($v) {
                return (is_array($v) && ! empty($v)) || $v != '';
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

    protected function stripCountFromResult(array &$result)
    {
        $count = -1;

        $rc = count($result);
        if (! empty($result) && isset($result[$rc - 1]['count'])) {
            $count = $result[$rc - 1]['count'];
            unset($result[$rc - 1]['count']);
        }

        return $count;
    }

    protected function fillKeys(array &$result, $check_flag = true) {
        if ($this->hasFlag(API::FLAG_FILL_KEYS) || !$check_flag) {
            $keys = $this->command->getResponseKeys();

            array_walk($keys, function ($key) use (&$result) {
                if (! isset($result[$key])) {
                    $result[$key] = null;
                }
            });
        }
    }

    protected function unwrap(array &$result, $check_flag = true) {
        if (count($result) == 1 && ($this->hasFlag(API::FLAG_UNWRAP) || !$check_flag)) {
            $result = $result[0];
        }
    }

    protected function unwrapKeys(array &$result, $check_flag = true)
    {
        if ($this->hasFlag(API::FLAG_UNWRAP_KEYS) || ! $check_flag) {
            $keys = $this->command->getResponseKeys();
            if (count($keys) == 1) {
                $result = array_column($result, $keys[0]);
            }
        }
    }

    protected function hasFlag($flag)
    {
        return in_array($flag, $this->flags);
    }
}