<?php


namespace Drift\Bus\Async;

use Drift\Bus\AsyncAdapter;
use Drift\Bus\CommandBus;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Promise\PromiseInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class FilesystemAdapter
 */
class FilesystemAdapter implements AsyncAdapter
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $filename;

    /**
     * FilesystemAdapter constructor.
     *
     * @param LoopInterface $loop
     * @param Filesystem $filesystem
     * @param string $filename
     */
    public function __construct(
        LoopInterface $loop,
        Filesystem $filesystem,
        string $filename
    )
    {
        $this->loop = $loop;
        $this->filesystem = $filesystem;
        $this->filename = $filename;
    }

    /**
     * @inheritDoc
     */
    public function enqueue($command): PromiseInterface
    {
        return $this
            ->filesystem
            ->file($this->filename)
            ->open('a')
            ->then(function(WritableStreamInterface $stream) use ($command) {
                $stream->write(serialize($command));
                $stream->close();
            });
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
        $handler = fopen($this->filename, 'r');

        while (true) {
            $content = fgets($handler);
            if (false === $content) {
                return;
            }

            $command = unserialize($content);
            $bus->execute($command);

            if (!is_null($printCommandConsumed)) {
                $printCommandConsumed($command);
            }
        }
    }
}