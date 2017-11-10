<?php


namespace Dehare\SCPHP\Parser;


use Dehare\SCPHP\API;
use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Parser\Result\ArrayResult;

class ArrayParser  extends SqueezeParser implements ParserInterface
{
    /** @var ArrayResult */
    protected $result;

    /**
     * Prepare data and set ArrayResult
     *
     * @return ArrayResult
     */
    protected function setResult()
    {
        $results = $this->splitByDelimiter($this->command->getDelimiter());
        $count   = $this->stripCountFromResult($results);
        $results = array_filter($results);

        $this->result = new ArrayResult($results, $count);

        if ($this->hasFlag(API::FLAG_FILL_KEYS) || ! empty($this->options['ignoreFlags'])) {
            $this->result->fillKeys($this->command->getResponseKeys());
        }

        if ($this->hasFlag(API::FLAG_UNWRAP_KEYS) || ! empty($this->options['ignoreFlags']) ) {
            $keys = $this->command->getResponseKeys();
            if (count($keys) === 1) {
                $this->result->unwrapKeys($keys[0]);
            }
        }

        if ($this->hasFlag(API::FLAG_UNWRAP) || ! empty($this->options['ignoreFlags']) ) {
            $this->result->unwrap();
        }
    }

    /**
     * @return ArrayResult
     */
    protected function getResult()
    {
        return $this->result;
    }
}