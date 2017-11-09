<?php

namespace Dehare\SCPHP\Command;

use Dehare\SCPHP\API;
use Dehare\SCPHP\Exception\CommandException;
use Dehare\SCPHP\Exception\InvalidCommandException;

class Command
{
    const QUERY_ARRAY   = 1;
    const QUERY_STRING  = 2;
    const QUERY_BOOL    = 3;
    const QUERY_INT     = 4;
    const QUERY_SUCCESS = 5;

    private $repository;
    private $config = [];
    private $key;

    private $ready   = false;
    private $command = null;
    private $escaped = null;
    private $params  = [];

    public function __construct(Repository $repository, $key)
    {
        $this->key        = $key;
        $this->repository = $repository;
        $this->config     = $repository->getCommandConfiguration($key);
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getCommand()
    {
        if (! $this->ready) {
            throw new CommandException('Command not initialized', 500);
        }

        return $this->command;
    }

    public function getEscapedCommand()
    {
        if (! $this->ready) {
            throw new CommandException('Command not initialized', 500);
        }

        return $this->escaped;
    }

    public function getQuery()
    {
        return ! empty($this->config['query']) ? $this->config['query'] : self::QUERY_BOOL;
    }

    public function getParser()
    {
        return $this->config['parser'];
    }

    public function getDelimiter()
    {
        $keys = $this->getResponseKeys();

        return ! empty($keys) ? $keys[0] : null;
    }

    /**
     * Get all keys available to command
     *
     * Keys change by the specification of tags
     *
     * @return array
     */
    public function getResponseKeys()
    {
        $keys = $this->config['response'];
        $tags = $this->config['tags'];

        if (! empty($this->params['tags'])) {
            $used_tags = array_diff(array_keys($tags), $this->params['tags']);
            $tags      = array_filter($tags, function ($k) use ($used_tags) {
                return $k != '_' && in_array($k, $used_tags);
            }, ARRAY_FILTER_USE_KEY);

            $keys = array_merge($keys, array_values($tags));
        }

        return $keys;
    }

    /**
     * Gets defaults flags for command
     * @return array
     */
    public function getFlags()
    {
        $result = [];
        $flags  = ! empty($this->config['flags']) ? $this->config['flags'] : [];
        foreach ($flags as $flag) {
            $result[$flag] = true;
        }

        return $result;
    }

    public function isReady()
    {
        return $this->ready;
    }

    /**
     * Compile a command
     *
     * Command looks at its configuration and loops through parameters
     * applying static and user defined parameters
     *
     * @param array $params
     */
    public function compile(array $params = [])
    {
        $this->command = $this->escaped = $this->key;

        if (! empty($this->config['_command'])) {
            $this->command = $this->escaped = $this->config['_command'];
        }

        if (! empty($this->config['prefix'])) {
            $this->append($this->config['prefix']);
        }
        if (! empty($this->config['limit'])) {
            $this->append(isset($params['start']) ? $params['start'] : 0);
            $this->append(isset($params['limit']) ? $params['limit'] : $this->config['limit']);
        }
        unset($params['start'], $params['limit']);

        if (! empty($params['command']) && ! empty($this->config['commands'])) {
            if (! in_array($params['command'], $this->config['commands'])) {
                throw new InvalidCommandException('Illegal command "' . $params['command'] . '" supplied ' . $this->key, 500);
            }
            $this->append($params['command']);
        };

        // set params from filters
        if (! empty($this->config['parameters'])) {
            foreach ($this->config['parameters'] as $p => $default) {
                if (! empty($params[$p]) || (isset($params[$p]) && $params[$p] !== null)) {
                    if (is_array($default) && isset($default['options'])) { // parameter has options
                        if (in_array($params[$p], $default['options'])) {
                            $this->setParam($p, $params[$p]);
                        } else {
                            throw new \InvalidArgumentException("Invalid option \"$params[$p]\" for \"$p\"");
                        }
                    } else {
                        $value = $params[$p];
                    }
                } elseif ($default !== null) {
                    if (is_array($default) && ! empty($default['_'])) { // parameter has default option
                        $value = $default['_'];
                    } elseif (! is_array($default)) {
                        $value = $default;
                    }
                }

                if (isset($value)) {
                    $this->setParam($p, $value);
                    unset($value);
                } else if ($this->requiredParameter($p)) {
                    throw new CommandException('Missing required parameter "'.$p.'" for "'.$this->key.'"');
                }
            }
        }

        $c_tags = ! empty($this->config['tags']) ? $this->config['tags'] : false;
        $tags   = ! empty($params['tags']) && ! empty($c_tags)
            ? $params['tags']
            : (! empty($c_tags['_'])
                ? $c_tags['_']
                : []);

        if (! empty($tags)) {
            if (is_array($tags)) {
                if (count($tags) === 1 && ($tags[0] === '*' || $tags[0] === '_')) {
                    unset($c_tags['_']);
                    $tags = array_keys($c_tags);
                } else {
                    $unused_tags = array_diff(array_keys($c_tags), $tags);
                    $tags        = array_diff($tags, $unused_tags);
                }
            } elseif ($tags === '*') {
                unset($c_tags['_']);
                $tags = array_keys($c_tags);
            } else {
                trigger_error('Unsupported tags format', E_USER_NOTICE);
                $tags = [];
            }

            $this->setParam('tags', $tags);
        }
        $this->finishCommand();
    }

    private function append($value)
    {
        $this->command .= ' ' . $value;
    }

    private function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    private function requiredParameter($key)
    {
        return !empty($this->config['requirements']) && in_array($key, $this->config['requirements']);
    }

    private function finishCommand()
    {
        $this->escaped = $this->command;
        $keyless = in_array(API::FILTER_KEYLESS, $this->config['filters']);

        foreach ($this->params as $key => $value) {
            if ($key == 'tags') {
                $value = implode('', $value);
            }
            $value = (is_bool($value) ? intval($value) : rawurlencode($value));


            $this->command .= ' '. ($keyless ? $value : "$key:$value");
            $this->escaped .= ' '. ($keyless ? $value : "$key%3A$value");
        }

        if (! empty($this->config['suffix'])) {
            $this->command .= ' ' . $this->config['suffix'];
            $this->escaped .= ' ' . $this->config['suffix'];
        }


        $this->command = trim($this->command);
        $this->escaped = trim($this->escaped);
        $this->ready   = true;
    }
}