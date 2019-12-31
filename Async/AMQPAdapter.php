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

use Bunny\Channel;
use Bunny\Message;
use Drift\Bus\Bus\CommandBus;
use Drift\Bus\Exception\InvalidCommandException;
use Drift\Console\OutputPrinter;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class AMQPAdapter.
 */
class AMQPAdapter extends AsyncAdapter
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var string
     */
    private $queueName;

    /**
     * RedisAdapter constructor.
     *
     * @param Channel       $channel
     * @param LoopInterface $loop
     * @param string        $queueName
     */
    public function __construct(
        Channel $channel,
        LoopInterface $loop,
        string $queueName
    ) {
        $this->channel = $channel;
        $this->loop = $loop;
        $this->queueName = $queueName;
    }

    /**
     * Prepare.
     *
     * @return PromiseInterface
     */
    public function prepare(): PromiseInterface
    {
        return $this
            ->channel
            ->queueDeclare($this->queueName, false, true);
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($command): PromiseInterface
    {
        return $this
            ->channel
            ->publish(serialize($command), [
                'delivery_mode' => 2,
            ], '', $this->queueName);
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

        $this
            ->prepare()
            ->then(function () use ($bus, $outputPrinter) {
                return $this
                    ->channel
                    ->qos(0, 1, true)
                    ->then(function () use ($bus, $outputPrinter) {
                        return $this
                            ->channel
                            ->consume(function (Message $message, Channel $channel) use ($bus, $outputPrinter) {
                                return $this
                                    ->executeCommand(
                                        $bus,
                                        unserialize($message->content),
                                        $outputPrinter,
                                        function () use ($message, $channel) {
                                            return $channel->ack($message);
                                        },
                                        function () use ($message, $channel) {
                                            return $channel->nack($message);
                                        },
                                        function () {
                                            $this
                                                ->loop
                                                ->stop();
                                        }
                                    );
                            }, $this->queueName);
                    });
            });

        $this
            ->loop
            ->run();
    }
}
