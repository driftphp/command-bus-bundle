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
interface AsyncAdapter
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
    public function enqueue($command): PromiseInterface;

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
    );
}
