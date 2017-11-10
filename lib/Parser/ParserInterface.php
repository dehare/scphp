<?php


namespace Dehare\SCPHP\Parser;


use Dehare\SCPHP\Command\Command;
use Dehare\SCPHP\Parser\Result\ResultInterface;

interface ParserInterface
{
    public function __construct(Command $command, array $flags = []);

    /**
     * Parse result to array
     *
     * @param string $data
     *
     * @return array
     */
    public function parse($data);
}