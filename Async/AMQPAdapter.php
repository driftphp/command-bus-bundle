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
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Drift\Bus\Bus\CommandBus;
use Drift\Bus\Exception\InvalidCommandException;
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
     * {@inheritdoc}
     */
    public function enqueue($command): PromiseInterface
    {
        return $this
            ->channel
            ->queueDeclare($this->queueName, false, true)
            ->then(function (MethodQueueDeclareOkFrame $frame) use ($command) {
                return $this
                    ->channel
                    ->publish(serialize($command), [
                        'delivery_mode' => 2,
                    ], '', $this->queueName);
            });
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
        $iterations = 0;

        $this
            ->channel
            ->qos(0, 1)
            ->then(function () use ($bus, $printCommandConsumed, $limit, &$iterations) {
                return $this
                    ->channel
                    ->consume(function (Message $message, Channel $channel) use ($bus, $printCommandConsumed, $limit, &$iterations) {
                        return $this
                            ->executeCommand(
                                $bus,
                                $message->content,
                                $printCommandConsumed
                            )
                            ->then(function () use ($message, $channel) {
                                $channel->ack($message);

                                return $message;
                            }, function () use ($message, $channel) {
                                $channel->nack($message);

                                return $message;
                            })
                            ->then(function (Message $message) use (&$limit, &$iterations, $channel) {
                                if (self::UNLIMITED !== $limit) {
                                    ++$iterations;
                                    if ($iterations >= $limit) {
                                        $this
                                            ->loop
                                            ->stop();

                                        return;
                                    }
                                }
                            });
                    }, $this->queueName);
            });

        $this
            ->loop
            ->run();
    }
}
