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

use Clue\React\Redis\Client;
use Drift\Bus\Bus\CommandBus;
use Drift\Bus\Exception\InvalidCommandException;
use function Clue\React\Block\await;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedisAdapter.
 */
class RedisAdapter extends AsyncAdapter
{
    /**
     * @var Client
     */
    private $redis;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var string
     */
    private $key;

    /**
     * RedisAdapter constructor.
     *
     * @param Client        $redis
     * @param LoopInterface $loop
     * @param string        $key
     */
    public function __construct(
        Client $redis,
        LoopInterface $loop,
        string $key
    ) {
        $this->redis = $redis;
        $this->loop = $loop;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($command): PromiseInterface
    {
        return $this
            ->redis
            ->rPush($this->key, serialize($command));
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

        while (true) {
            $promise = $this
                ->redis
                ->blPop($this->key, 0)
                ->then(function (array $job) use ($bus, $output) {
                    return $this->executeCommand(
                        $bus,
                        unserialize($job[1]),
                        $output,
                        function () {},
                        function () {},
                        function () {}
                    );
                });

            $wasLastOne = await($promise, $this->loop);
            if ($wasLastOne) {
                return;
            }
        }
    }
}
