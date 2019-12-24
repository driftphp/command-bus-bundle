<?php

namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Class CommandBus
 */
class CommandBus
{
    /**
     * @var Bus
     */
    private $bus;

    /**
     * QueryBus constructor.
     *
     * @param Bus $bus
     */
    public function __construct(Bus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Execute command
     *
     * @param Object $query
     *
     * @return PromiseInterface
     */
    public function execute($query) : PromiseInterface
    {
        return $this
            ->bus
            ->handle($query)
            ->then(function() {
                return;
            });
    }
}