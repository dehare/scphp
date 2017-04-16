<?php

namespace Dehare\SCPHP;

use Dehare\SCPHP\Exception\RequestException;

class Request
{
    const LF           = "\n";
    const QUERY_ARRAY  = 1;
    const QUERY_STRING = 2;
    const QUERY_BOOL   = 3;
    const QUERY_INT    = 4;

    private static $commands = [];
    private static $_config  = [];
    private static $_cmd     = [];

    public static function query($key, array $filters = [], array $options = [])
    {
        $configuration = null;
        if (strpos($key, ':')) {
            list($configuration, $key) = explode(':', $key);
        }

        if ($configuration) {
            self::setCommands($configuration);
        }

        self::setConfig($key);
        $command = self::compile($key, $filters, $options);
        $query   = ! empty(self::$_config['query']) ? self::$_config['query'] : self::QUERY_BOOL;

        $data = self::command($command);
        switch ($query) {
            case self::QUERY_ARRAY:
                $result = self::getArray($data, $filters, $options);
                break;
            case self::QUERY_BOOL:
                $result = self::validateBoolean($data);
                break;
            case self::QUERY_INT:
                $result = self::validateInteger($data);
                break;
            default:
                $result = $data;
        }
        self::$_config = [];

        return $result;
    }

    public static function post($key)
    {
    }

    public function getArray($data, array $filters = [], array $options = [])
    {
        $results   = [];
        $delimiter = self::$_config['response'][0];
        $data      = array_filter(explode(' ' . $delimiter, ' ' . $data));

        foreach ($data as $result) {
            $columns    = explode(' ', $result);
            $columns[0] = $delimiter . $columns[0];
            $line       = [];
            array_walk($columns, function ($v) use (&$line) {
                $v = urldecode($v);
                $line[strstr($v, ':', true)] = ltrim(strstr($v, ':'), ':');
            });

            $results[] = $line;
        }

        $count = !empty($results) ? $results[count($results) - 1]['count'] : -1;

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

    public static function setCommands($config = [])
    {
        if (is_array($config)) {
            self::$commands = [];
        } else {
            $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $config . '.php';
            if (! is_file($path)) {
                throw new \InvalidArgumentException("Configuration \"$config\" doesn't exist");
            }
            self::$commands = (include $path);
        }
    }

    private static function setConfig($key)
    {
        if (empty(self::$commands)) {
            self::setCommands('main');
        }

        if (! isset(self::$commands[$key])) {
            throw new \InvalidArgumentException("Command \"$key\" is not configured");
        }

        self::$_config = self::$commands[$key];
    }

    /**
     * @param       $command
     * @param array $filters
     * @param array $options
     * @return mixed|string
     */
    private static function compile($command, array $filters = [], array $options = [])
    {
        $c = self::$_config;
        if (! empty($c['command'])) {
            self::$_cmd = [$c['command'], $c['command']];

            return self::$_cmd[0]; // todo append params
        }

        self::$_cmd = [$command, $command];
        if (! empty($c['prepend'])) {
            self::addCommandTag($c['prepend']);
        }
        if (! empty($c['limit'])) {
            self::addCommandTag(isset($options['start']) ? $options['start'] : 0);
            self::addCommandTag(isset($options['limit']) ? $options['limit'] : $c['limit']);
        }

        // set params from filters
        if (! empty($c['parameters'])) {
            foreach ($c['parameters'] as $p => $default) {
                if (! empty($filters[$p]) || (isset($filters[$p]) && $filters[$p] !== null)) {
                    if (is_array($default) && isset($default['options'])) {
                        if (in_array($filters[$p], $default['options'])) {
                            self::addCommandTag($p, $filters[$p]);
                        } else {
                            throw new \InvalidArgumentException("Invalid option \"$filters[$p]\" for \"$p\"");
                        }
                    } else {
                        $value = $filters[$p];
                    }
                } elseif ($default !== null) {
                    if (is_array($default) && ! empty($default['_'])) {
                        $value = $default['_'];
                    } elseif (! is_array($default)) {
                        $value = $default;
                    }
                }

                if (isset($value)) {
                    self::addCommandTag($p, $value);
                    unset($value);
                }
            }
        }

        $c_tags = ! empty($c['tags']) ? $c['tags'] : false;
        $tags   = ! empty($filters['tags']) && ! empty($c_tags)
            ? $filters['tags']
            : (! empty($c_tags['_'])
                ? $c_tags['_']
                : []);

        if (! empty($tags)) {
            if (is_array($tags)) {
                $unused_tags = array_diff($tags, array_keys($c_tags));
                $tags        = array_diff($tags, $unused_tags);
            } elseif ($tags === '*') {
                unset($c_tags['_']);
                $tags = array_keys($c_tags);
            } else {
                trigger_error('Unsupported tags format', E_USER_NOTICE);
                $tags = [];
            }

            self::addCommandTag('tags', implode('', $tags));
        }

        return self::$_cmd[0];
    }

    public static function addCommandTag($tag, $value = null)
    {
        $command = self::$_cmd[0];
        $command .= ' ' . $tag;
        if ($value) {
            $command .= ':' . (is_bool($value) ? intval($value) : rawurlencode($value));
        }

        $command_esc = str_replace(':', '%3A', $command);
        self::$_cmd  = [$command, $command_esc];
    }

    public static function command($command = null)
    {
        $cmd     = ! empty(self::$_cmd[0]) ? self::$_cmd[0] : null;
        $cmd_esc = ! empty(self::$_cmd[1]) ? self::$_cmd[1] : null;
        $command = trim($command ?: $cmd);

        if (empty($command)) {
            throw new RequestException('Can not send empty request', 500);
        }

        $result = false;
        $io     = fwrite(Connection::socket(), $command . self::LF);
        if ($io) {
            $result  = fgets(Connection::socket());
            $command = rtrim($command, "? \n");
            $result  = trim(rtrim(str_replace([$command, $cmd_esc], '', $result), "\n"));
        }

        return trim($result);
    }
}