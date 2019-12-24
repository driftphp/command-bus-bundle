<?php


namespace Drift\Bus\Async;

use Clue\React\Redis\Client;
use Drift\Bus\AsyncAdapter;
use Drift\Bus\CommandBus;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function Clue\React\Block\await;

/**
 * Class RedisAdapter
 */
class RedisAdapter implements AsyncAdapter
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
     * @param Client $redis
     * @param LoopInterface $loop
     * @param string $key
     */
    public function __construct(
        Client $redis,
        LoopInterface $loop,
        string $key
    )
    {
        $this->redis = $redis;
        $this->loop = $loop;
        $this->key = $key;
    }

    /**
     * @inheritDoc
     */
    public function enqueue($command): PromiseInterface
    {
        return $this
            ->redis
            ->rPush($this->key, serialize($command));
    }

    /**
     * Consume
     *
     * @param CommandBus $bus
     * @param int $limit
     * @param Callable $printCommandConsumed
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        Callable $printCommandConsumed = null
    )
    {
        $iterations = 0;
        while (true) {

            $promise = $this
                ->redis
                ->blPop($this->key, 0)
                ->then(function(array $job) use ($bus, $printCommandConsumed) {
                    $command = unserialize($job[1]);
                    $bus->execute($command);

                    if (!is_null($printCommandConsumed)) {
                        $printCommandConsumed($command);
                    }
                });

            await($promise, $this->loop);

            if (self::UNLIMITED !== $limit) {
                $iterations++;
                if ($iterations >= $limit) {
                    return;
                }
            }
        }
    }
}