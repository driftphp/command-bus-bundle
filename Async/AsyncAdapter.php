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
use Drift\Bus\Exception\MissingHandlerException;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * Prepare.
     *
     * @return PromiseInterface
     */
    public function prepare(): PromiseInterface
    {
        return new FulfilledPromise();
    }

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
     * @param CommandBus      $bus
     * @param int             $limit
     * @param OutputInterface $output
     *
     * @throws InvalidCommandException
     */
    abstract public function consume(
        CommandBus $bus,
        int $limit,
        OutputInterface $output
    );

    /**
     * Execute command.
     *
     * @param CommandBus      $bus
     * @param object          $command
     * @param OutputInterface $output
     * @param callable        $ok
     * @param callable        $ko
     * @param callable        $finish
     *
     * @return PromiseInterface
     */
    protected function executeCommand(
        CommandBus $bus,
        $command,
        OutputInterface $output,

        callable $ok,
        callable $ko,
        callable $finish
    ): PromiseInterface {
        return $bus
            ->execute($command)
            ->then(function () use ($output, $command, $ok, $finish) {
                $this->printCommandMessage($command, $output, 'consumed');

                return (new FulfilledPromise())
                    ->then(function () use ($ok) {
                        return $ok();
                    })
                    ->then(function () use ($finish) {
                        if (!$this->canConsumeAnotherOne()) {
                            return (new FulfilledPromise())
                                ->then(function () use ($finish) {
                                    return $finish();
                                })
                                ->then(function () {
                                    return true;
                                });
                        }

                        return false;
                    });
            }, function (\Exception $exception) use ($output, $command, $ok, $ko) {
                $ignorable = $exception instanceof MissingHandlerException;
                $ignorable
                    ? $this->printCommandMessage($command, $output, 'ignored')
                    : $this->printCommandMessage($command, $output, 'failed');

                return (
                    $ignorable
                        ? (new FulfilledPromise())
                            ->then(function () use ($ok) {
                                return $ok();
                            })
                        : (new FulfilledPromise())
                            ->then(function () use ($ko) {
                                return $ko();
                            })
                    )
                    ->then(function () {
                        return false;
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

    /**
     * Print command consumed.
     *
     * @param object          $command
     * @param OutputInterface $output
     * @param string          $status
     */
    private function printCommandMessage(
        $command,
        OutputInterface $output,
        string $status
    ) {
        $commandNamespace = get_class($command);
        $commandParts = explode('\\', $commandNamespace);
        $commandClass = end($commandParts);
        $output->writeln(sprintf('Command <%s> %s', $commandClass, $status));
    }
}
