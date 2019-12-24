<?php


namespace Drift\Bus\Async;

use Drift\Bus\AsyncAdapter;
use Drift\Bus\CommandBus;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class DummyAdapter
 */
class InMemoryAdapter implements AsyncAdapter
{
    /**
     * @var array
     */
    private $queue = [];

    /**
     * @inheritDoc
     */
    public function enqueue($command): PromiseInterface
    {
        $this->queue[] = $command;

        return new FulfilledPromise();
    }

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
    )
    {
        foreach ($this->queue as $command) {
            $bus->execute($command);

            if (!is_null($printCommandConsumed)) {
                $printCommandConsumed($command);
            }
        }
    }
}