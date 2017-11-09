<?php


namespace Dehare\SCPHP\Parser;


use Dehare\SCPHP\API;
use Dehare\SCPHP\Command\Command;

class ArrayParser implements ParserInterface
{
    /** @var Command */
    private $command;
    /** @var string */
    private $data;
    /** @var array */
    private $flags = [];

    public function __construct(Command $command, array $flags = [])
    {
        $this->command = $command;
        $this->flags   = $flags;
    }

    /**
     * @inheritdoc
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

            foreach ($columns as $col) {
                $col                           = urldecode($col);
                $line[strstr($col, ':', true)] = ltrim(strstr($col, ':'), ':');
            }

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
     * Fills keys with nulls if flag is provided
     *
     * @param array $result
     * @param bool  $check_flag Only fill if flag is set
     *
     * @see API::FLAG_FILL_KEYS
     */
    protected function fillKeys(array &$result, $check_flag = true)
    {
        if ($this->hasFlag(API::FLAG_FILL_KEYS) || ! $check_flag) {
            $keys = $this->command->getResponseKeys();

            foreach ($keys as $key) {
                if (! isset($result[$key])) {
                    $result[$key] = null;
                }
            }
        }
    }

    /**
     * Unwrap results if flag is provided
     *
     * @param array $result
     * @param bool  $check_flag Only unwrap if flag is set
     *
     * @see API::FLAG_UNWRAP
     */
    protected function unwrap(array &$result, $check_flag = true)
    {
        if (count($result) == 1 && ($this->hasFlag(API::FLAG_UNWRAP) || ! $check_flag)) {
            $result = $result[0];
        }
    }

    /**
     * Unwrap result subsets if flag is provided
     *
     * @param array $result
     * @param bool  $check_flag Only unwrap if flag is set
     */
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