<?php

namespace Dehare\SCPHP\Command;

use Dehare\SCPHP\API;

class Repository
{
    private $configuration = [];
    private $history       = [];

    /**
     * CommandRepository constructor.
     *
     * @param $key
     */
    public function __construct($key)
    {
        $this->configuration = is_array($key) ? $key : API::getConfig($key);
        $this->validateConfiguration();
    }

    public function getPreviousCommand()
    {
        $h = $this->history;

        return end($h);
    }

    public function getCommandConfiguration($key)
    {
        if (! isset($this->configuration[$key])) {
            throw new \InvalidArgumentException("Command \"$key\" is not configured");
        }

        return $this->configuration[$key];
    }

    /**
     * Add a completed command to history
     *
     * @param Command $command
     */
    public function registerCommand(Command $command)
    {
        if ($command->isReady()) {
            $this->history[] = $command;
        }
    }

    private function validateConfiguration()
    {
        foreach ($this->configuration as &$cmd) {
            $cmd['response'] = ! empty($cmd['response']) ? $cmd['response'] : [];
            $cmd['tags']     = ! empty($cmd['tags']) ? $cmd['tags'] : [];
            $cmd['limit']    = ! empty($cmd['limit']) ? $cmd['limit'] : false;
            $cmd['query']    = ! empty($cmd['query']) ? $cmd['query'] : Command::QUERY_SUCCESS;
        }
    }
}