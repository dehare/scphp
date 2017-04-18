<?php

namespace Dehare\SCPHP\Command;

class CommandRepository
{
    private $configuration = [];
    private $history       = [];

    /**
     * CommandRepository constructor.
     * @param $key
     *
     * @todo parse / validate configuration
     */
    public function __construct($key)
    {
        if (is_array($key)) {
            $this->configuration = $key;
        } else {
            $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $key . '.php';
            if (! is_file($path)) {
                throw new \InvalidArgumentException("Could not initialize repo for \"$key\".", 404);
            }
            $this->configuration = (include $path);
        }

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