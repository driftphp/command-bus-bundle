<?php

namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Class QueryBus
 */
class QueryBus
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
     * Ask query
     *
     * @param Object $query
     *
     * @return PromiseInterface
     */
    public function ask($query) : PromiseInterface
    {
        return $this
            ->bus
            ->handle($query);
    }
}