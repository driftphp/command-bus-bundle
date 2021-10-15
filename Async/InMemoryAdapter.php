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

namespace Drift\CommandBus\Async;

use function Clue\React\Block\await;
use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\NonRecoverableCommand;
use Drift\CommandBus\Console\CommandBusLineMessage;
use Drift\CommandBus\Exception\InvalidCommandException;
use Drift\Console\OutputPrinter;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DummyAdapter.
 */
class InMemoryAdapter extends AsyncAdapter
{
    /**
     * @var array
     */
    private $queue = null;

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
     * Create infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function createInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        $this->queue = [];
        (new CommandBusLineMessage('Local queue created properly'))->print($outputPrinter);

        return resolve();
    }

    /**
     * Drop infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function dropInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        $this->queue = null;
        (new CommandBusLineMessage('Local queue dropped properly'))->print($outputPrinter);

        return resolve();
    }

    /**
     * Check infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function checkInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        is_array($this->queue)
            ? (new CommandBusLineMessage('Local queue exists'))->print($outputPrinter)
            : (new CommandBusLineMessage('Local queue does not exist'))->print($outputPrinter);

        return resolve();
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'In Memory (Not for production)';
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

        return resolve();
    }

    /**
     * Consume.
     *
     * @param CommandBus    $bus
     * @param int           $limit
     * @param OutputPrinter $outputPrinter
     *
     * @throws InvalidCommandException
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        OutputPrinter $outputPrinter
    ) {
        $this->resetIterations($limit);

        foreach ($this->queue as $key => $command) {
            $notShouldRecover = !$command instanceof NonRecoverableCommand;
            if ($notShouldRecover) {
                unset($this->queue[$key]);
            }

            $promise = $this
                ->executeCommand(
                    $bus,
                    $command,
                    $outputPrinter,
                    function () use ($key, $notShouldRecover) {
                        if (!$notShouldRecover) {
                            unset($this->queue[$key]);
                        }
                    },
                    function () {},
                    function () {
                        return true;
                    }
                );

            $wasLastOne = await($promise, $this->loop);
            if ($wasLastOne) {
                return;
            }
        }
    }
}
