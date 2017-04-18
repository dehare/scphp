<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Command\CommandRepository;
use Dehare\SCPHP\Exception\RequestException;

class Request
{
    const LF = "\n";

    private static $_repos       = [];
    private static $_active_repo = null;
    /**
     * @var Command
     */
    private static $_cmd = null;

    public static function query($key, array $filters = [], array $options = [])
    {
        self::setup($key);
        self::$_cmd->compile($filters, $options);

        $data = self::execute();
        switch (self::$_cmd->getQuery()) {
            case Command::QUERY_ARRAY:
                $result = self::getArray($data, $options);
                break;
            case Command::QUERY_BOOL:
                $result = self::validateBoolean($data);
                break;
            case Command::QUERY_SUCCESS:
                $result = ! empty($data);
                break;
            case Command::QUERY_INT:
                $result = self::validateInteger($data);
                break;
            default:
                $result = $data;
        }
        self::$_repos[self::$_active_repo]->registerCommand(self::$_cmd);
        self::$_cmd = null;

        return $result;
    }

    /**
     * Stub for simple post
     * @param $key
     *
     * @todo
     */
    public static function post($key)
    {
    }

    public function getArray($data, array $options = [])
    {
        $results   = [];
        $delimiter = self::$_cmd->getDelimiter();

        $data = $delimiter ? array_filter(explode(' ' . $delimiter, ' ' . $data)) : (array)$data;
        foreach ($data as $result) {
            $columns    = explode(' ', $result);
            $columns[0] = $delimiter . $columns[0];
            $line       = [];
            array_walk($columns, function ($v) use (&$line) {
                $v                           = urldecode($v);
                $line[strstr($v, ':', true)] = ltrim(strstr($v, ':'), ':');
            });

            if (! empty($options['fill_tags'])) {
                $keys = self::$_cmd->getResponseKeys();
                array_walk($keys, function ($key) use (&$line) {
                    if (! isset($line[$key])) {
                        $line[$key] = null;
                    }
                });
            }

            $results[] = $line;
        }

        $rc    = count($results);
        //$count = -1;
        if (! empty($results) && ! empty($results[$rc - 1]['count'])) {
            $count = $results[$rc - 1]['count'];
            unset($results[$rc - 1]['count']);
        }

        return compact('results', 'count');
    }

    public function validateBoolean($data)
    {
        if (preg_match('/(\w\s)+/', $data)) {
            trigger_error("Could not determine boolean on \"$data\"", E_USER_WARNING);

            return true;
        }

        return filter_var($data, FILTER_VALIDATE_BOOLEAN);
    }

    public static function execute(Command $command = null)
    {
        if (! empty($command)) {
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

    private static function setup($command)
    {
        $repo = self::$_active_repo;
        if (strpos($command, ':')) {
            list($repo, $command) = explode(':', $command);
        }

        $repository = self::repository($repo ?: 'main');
        self::$_cmd = new Command($repository, $command);
    }

    private static function repository($key)
    {
        if (isset(self::$_repos[$key])) {
            return self::$_repos[$key];
        }

        $repo               = new CommandRepository($key);
        self::$_repos[$key] = $repo;
        self::$_active_repo = $key;

        return $repo;
    }
}