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
use function Clue\React\Block\await;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DummyAdapter.
 */
class InMemoryAdapter extends AsyncAdapter
{
    /**
     * @var array
     */
    private $queue = [];

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * InMemoryAdapter constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

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
     * @param CommandBus      $bus
     * @param int             $limit
     * @param OutputInterface $output
     *
     * @throws InvalidCommandException
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        OutputInterface $output
    ) {
        $this->resetIterations($limit);

        foreach ($this->queue as $key => $command) {
            $promise = $this
                ->executeCommand(
                    $bus,
                    $command,
                    $output,
                    function () use ($key) {
                        unset($this->queue[$key]);
                    },
                    function () {},
                    function () {}
                );

            $wasLastOne = await($promise, $this->loop);
            if ($wasLastOne) {
                return;
            }
        }
    }
}
