<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Bus\Async;

use Drift\Bus\Bus\CommandBus;
use Drift\Bus\Exception\InvalidCommandException;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class DummyAdapter.
 */
class InMemoryAdapter implements AsyncAdapter
{
    /**
     * @var array
     */
    private $queue = [];

    /**
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($command): PromiseInterface
    {
        $this->queue[] = $command;

        return new FulfilledPromise();
    }

    /**
     * Consume.
     *
     * @param CommandBus $bus
     * @param int        $limit
     * @param callable   $printCommandConsumed
     *
     * @throws InvalidCommandException
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        callable $printCommandConsumed = null
    ) {
        foreach ($this->queue as $command) {
            $bus->execute($command);

            if (!is_null($printCommandConsumed)) {
                $printCommandConsumed($command);
            }
        }
    }
}
