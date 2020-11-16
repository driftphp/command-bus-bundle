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

use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Console\CommandConsumedLineMessage;
use Drift\CommandBus\Exception\InvalidCommandException;
use Drift\CommandBus\Exception\MissingHandlerException;
use Drift\Console\OutputPrinter;
use Drift\Console\TimeFormatter;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Interface AsyncAdapter.
 */
abstract class AsyncAdapter
{
    /**
     * @var int
     */
    const UNLIMITED = 0;

    /**
     * @var int
     */
    private $iterations = 0;

    /**
     * @var int
     */
    private $limit = self::UNLIMITED;

    /**
     * Get adapter name.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Create infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    abstract public function createInfrastructure(OutputPrinter $outputPrinter): PromiseInterface;

    /**
     * Drop infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    abstract public function dropInfrastructure(OutputPrinter $outputPrinter): PromiseInterface;

    /**
     * Check infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    abstract public function checkInfrastructure(OutputPrinter $outputPrinter): PromiseInterface;

    /**
     * Enqueue.
     *
     * @param object $command
     *
     * @return PromiseInterface
     */
    abstract public function enqueue($command): PromiseInterface;

    /**
     * Consume.
     *
     * @param CommandBus    $bus
     * @param int           $limit
     * @param OutputPrinter $outputPrinter
     *
     * @throws InvalidCommandException
     */
    abstract public function consume(
        CommandBus $bus,
        int $limit,
        OutputPrinter $outputPrinter
    );

    /**
     * Execute command.
     *
     * @param CommandBus    $bus
     * @param object        $command
     * @param OutputPrinter $outputPrinter
     * @param callable      $ok
     * @param callable      $ko
     * @param callable      $finish
     *
     * @return PromiseInterface
     */
    protected function executeCommand(
        CommandBus $bus,
        $command,
        OutputPrinter $outputPrinter,

        callable $ok,
        callable $ko,
        callable $finish
    ): PromiseInterface {
        $from = microtime(true);

        return $bus
            ->execute($command)
            ->then(function () use ($from, $outputPrinter, $command, $ok, $finish) {
                $to = microtime(true);

                (new CommandConsumedLineMessage(
                    $command,
                    TimeFormatter::formatTime($to - $from),
                    CommandConsumedLineMessage::CONSUMED
                ))->print($outputPrinter);

                return resolve()
                    ->then(function () use ($ok) {
                        return $ok();
                    })
                    ->then(function () use ($finish) {
                        if (!$this->canConsumeAnotherOne()) {
                            return (resolve())
                                ->then(function () use ($finish) {
                                    return $finish();
                                });
                        }

                        return null;
                    });
            }, function (\Exception $exception) use ($from, $outputPrinter, $command, $ok, $ko) {
                $to = microtime(true);
                $ignorable = $exception instanceof MissingHandlerException;

                (new CommandConsumedLineMessage(
                    $command,
                    TimeFormatter::formatTime($to - $from),
                    $ignorable
                        ? CommandConsumedLineMessage::IGNORED
                        : CommandConsumedLineMessage::REJECTED
                ))->print($outputPrinter);

                return (
                    $ignorable
                        ? (resolve())
                            ->then(function () use ($ok) {
                                return $ok();
                            })
                        : (resolve())
                            ->then(function () use ($ko) {
                                return $ko();
                            })
                    )
                    ->then(function () {
                        return null;
                    });
            });
    }

    /**
     * Reset iterations.
     *
     * @param int $limit
     */
    public function resetIterations(int $limit)
    {
        $this->iterations = 0;
        $this->limit = $limit;
    }

    /**
     * Can consume another one.
     *
     * @return
     * @return bool
     */
    public function canConsumeAnotherOne(): bool
    {
        if (self::UNLIMITED !== $this->limit) {
            ++$this->iterations;

            if ($this->iterations >= $this->limit) {
                return false;
            }
        }

        return true;
    }
}
