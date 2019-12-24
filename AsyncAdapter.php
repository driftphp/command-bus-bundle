<?php


namespace Drift\Bus;


use React\Promise\PromiseInterface;

/**
 * Interface AsyncAdapter
 */
interface AsyncAdapter
{
    /**
     * @var int
     */
    const UNLIMITED = -1;

    /**
     * Enqueue
     *
     * @param object $command
     *
     * @return PromiseInterface
     */
    public function enqueue($command) : PromiseInterface;

    /**
     * Consume
     *
     * @param CommandBus $bus
     * @param int $limit
     * @param Callable $printCommandConsumed
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        Callable $printCommandConsumed = null
    );
}