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
use React\Promise\PromiseInterface;

/**
 * Interface AsyncAdapter.
 */
abstract class AsyncAdapter
{
    /**
     * @var int
     */
    const UNLIMITED = -1;

    /**
     * Enqueue.
     *
     * @param object $command
     *
     * @return PromiseInterface
     */
    public abstract function enqueue($command): PromiseInterface;

    /**
     * Consume.
     *
     * @param CommandBus $bus
     * @param int        $limit
     * @param callable   $printCommandConsumed
     *
     * @throws InvalidCommandException
     */
    public abstract function consume(
        CommandBus $bus,
        int $limit,
        callable $printCommandConsumed = null
    );

    /**
     * Execute command
     *
     * @param CommandBus $bus
     * @param string $job
     * @param callable   $printCommandConsumed
     *
     * @return PromiseInterface
     */
    protected function executeCommand(
        CommandBus $bus,
        string $job,
        callable $printCommandConsumed
    ) : PromiseInterface
    {
        $command = unserialize($job);

        return $bus
            ->execute($command)
            ->then(function() use ($printCommandConsumed, $command) {
                if (!is_null($printCommandConsumed)) {
                    $printCommandConsumed($command);
                }
            });
    }
}
