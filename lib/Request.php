<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Command\Repository;
use Dehare\SCPHP\Parser\ParserInterface;

class Request
{
    const LF = "\n";

    private static $_repos       = [];
    private static $_active_repo = null;
    /**
     * @var Command
     */
    private static $_cmd = null;

    /**
     * Send and parse command from known commands
     *
     * @param string $key
     * @param array  $params
     * @param array  $flags
     *
     * @return array|bool|int|mixed|string
     *
     * @todo add link to docs
     */
    public static function query($key, array $params = [], array $flags = [])
    {
        self::setup($key, $params);
        self::$_cmd->compile($params);

        $data  = self::execute();
        $flags = API::filterFlags(self::$_cmd, $flags);

        $result = self::parse($data, $flags);

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

    /**
     * Send command to CLI and parse resultset to usable data
     *
     * @param Command|null $command
     *
     * @return string
     */
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

    /**
     * Parse data into usable return format
     *
     * @param string $data
     * @param array $flags
     *
     * @return mixed
     */
    private static function parse($data, array $flags = [])
    {
        if (in_array(API::FLAG_RAW, $flags)) {
            return $data;
        }

        $query = self::$_cmd->getQuery();
        /** @var ParserInterface $parser */
        $parser = self::$_cmd->getParser();

        if ($parser) {
            $parser = new $parser(self::$_cmd, $flags);
            $result = $parser->parse($data);
        } else {
            switch ($query) {
                case Command::QUERY_BOOL:
                    $result = self::validateBoolean($data);
                    break;
                case Command::QUERY_INT:
                    $result = self::validateInteger($data);
                    break;
                case Command::QUERY_SUCCESS:
                    $result = ! empty($data);
                    break;
                default:
                    $result = $data;
            }
        }

        return $result;

    }

    /**
     * Parse data to boolean
     *
     * @param string $data
     *
     * @return bool
     */
    public static function validateBoolean($data)
    {
        if (preg_match('/(\w\s)+/', $data)) {
            trigger_error("Could not determine boolean on \"$data\"", E_USER_WARNING);

            return true;
        }

        return filter_var($data, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Parse data to integer
     *
     * @param string $data
     *
     * @return int
     */
    public static function validateInteger($data)
    {
        if (preg_match('/(\d)+/', $data, $m)) {
            return intval($m[1]);
        }

        return 0;
    }

    /**
     * Initialize command
     *
     * @param string $command
     * @param array  $params
     */
    private static function setup($command, array &$params)
    {
        $repo = self::$_active_repo;
        if (strpos($command, ':')) {
            preg_match('/(\w+):(\w+):?(\w+)?/', $command, $match);
            list($input, $repo, $command) = $match;
            if (! empty($match[3])) {
                $params['command'] = $match[3];
            }
        }

        $repository = self::repository($repo ?: 'main');
        self::$_cmd = new Command($repository, $command);
    }

    /**
     * Initialize command repository
     *
     * @param string $key
     *
     * @return Repository
     */
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