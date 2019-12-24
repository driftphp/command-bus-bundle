<?php


namespace Drift\Bus;

/**
 * Class AsyncCommand
 */
class AsyncCommand
{
    /**
     * @var object
     */
    private $command;

    /**
     * AsyncCommand constructor.
     *
     * @param object $command
     */
    public function __construct(object $command)
    {
        $this->command = $command;
    }

    /**
     * Get command
     *
     * @return $command
     */
    public function getCommand()
    {
        return $this->command;
    }
}