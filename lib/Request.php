<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Command\Repository;

class Request
{
    const LF = "\n";

    private static $_repos       = [];
    private static $_active_repo = null;
    /**
     * @var Command
     */
    private static $_cmd = null;

    public static function query($key, array $params = [], array $flags = [])
    {
        self::setup($key, $params);
        self::$_cmd->compile($params);

        $data  = self::execute();
        $flags = API::filterFlags(self::$_cmd, $flags);

        if (!in_array(API::FLAG_RAW, $flags)) {
            switch (self::$_cmd->getQuery()) {
                case Command::QUERY_ARRAY:
                    $result = self::getArray($data, $flags);
                    break;
                case Command::QUERY_BOOL:
                    $result = self::validateBoolean($data);
                    break;
                case Command::QUERY_SUCCESS:
                    $result = !empty($data);
                    break;
                case Command::QUERY_INT:
                    $result = self::validateInteger($data);
                    break;
                default:
                    $result = $data;
            }
        }

        self::$_repos[self::$_active_repo]->registerCommand(self::$_cmd);
        self::$_cmd = null;

        return isset($result) ? $result : $data;
    }

    /**
     * Stub for simple post
     *
     * @param $key
     *
     * @todo
     */
    public static function post($key)
    {
    }

    public function getArray($data, array $flags = [])
    {
        $results = [];
        if (in_array(API::FLAG_COUNT_ONLY, $flags)) {
            $count = -1;
            if (preg_match('/count%3A(\d+)$/', $data, $m)) {
                $count = $m[1];
            }

            return intval($count);
        }

        $delimiter = self::$_cmd->getDelimiter();

        $data = $delimiter ? array_filter(explode(' ' . $delimiter, ' ' . $data)) : (array)$data;
        foreach ($data as $d) {
            $line       = [];
            $columns    = explode(' ', $d);
            $columns[0] = $delimiter . $columns[0];

            array_walk($columns, function ($v) use (&$line) {
                $v                           = urldecode($v);
                $line[strstr($v, ':', true)] = ltrim(strstr($v, ':'), ':');
            });

            if (in_array(API::FLAG_FILL_KEYS, $flags)) {
                $keys = self::$_cmd->getResponseKeys();
                array_walk($keys, function ($key) use (&$line) {
                    if (!isset($line[$key])) {
                        $line[$key] = null;
                    }
                });
            }

            $results[] = $line;
        }

        $rc = count($results);
        if (!empty($results) && !empty($results[$rc - 1]['count'])) {
            $count = $results[$rc - 1]['count'];
            unset($results[$rc - 1]['count']);
        }

        if (in_array(API::FLAG_UNWRAP_KEYS, $flags)) {
            $keys = self::$_cmd->getResponseKeys();
            if (count($keys) == 1) {
                $result = array_column($results, $keys[0]);
            }
        }

        if (!isset($result)) {
            $result = compact('results', 'count');
            if (in_array(API::FLAG_UNWRAP, $flags)) {
                if (count($results) == 1) {
                    $result = $results[0];
                }
            }
        }

        return $result;
    }

    public function validateBoolean($data)
    {
        if (preg_match('/(\w\s)+/', $data)) {
            trigger_error("Could not determine boolean on \"$data\"", E_USER_WARNING);

            return true;
        }

        return filter_var($data, FILTER_VALIDATE_BOOLEAN);
    }

    public function validateInteger($data)
    {
        if (preg_match('/(\d)+/', $data, $m)) {
            return intval($m[1]);
        }

        return 0;
    }

    public static function execute(Command $command = null)
    {
        if (!empty($command)) {
            self::$_cmd = $command;
        }

        $command = self::$_cmd->getCommand();

        $result = false;
        $io     = fwrite(Connection::socket(), $command . self::LF);
        if ($io) {
            $result  = fgets(Connection::socket());
            $command = rtrim($command, "? " . self::LF);
            $result  = trim(rtrim(str_replace([$command, self::$_cmd->getEscapedCommand()], '', $result), "\n"));
        }

        return trim($result);
    }

    private static function setup($command, array &$params)
    {
        $repo = self::$_active_repo;
        if (strpos($command, ':')) {
            preg_match('/(\w+):(\w+):?(\w+)?/', $command, $match);
            list($input, $repo, $command) = $match;
            if (!empty($match[3])) {
                $params['command'] = $match[3];
            }
        }

        $repository = self::repository($repo ?: 'main');
        self::$_cmd = new Command($repository, $command);
    }

    private static function repository($key)
    {
        if (isset(self::$_repos[$key])) {
            return self::$_repos[$key];
        }

        $repo               = new Repository($key);
        self::$_repos[$key] = $repo;
        self::$_active_repo = $key;

        return $repo;
    }
}