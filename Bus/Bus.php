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

namespace Drift\CommandBus\Bus;

use Drift\CommandBus\Exception\InvalidCommandException;
use Drift\CommandBus\Middleware\DebugableMiddleware;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

/**
 * Interface Bus.
 */
abstract class Bus
{
    /**
     * @var string
     */
    const DISTRIBUTION_INLINE = 'inline';

    /**
     * @var string
     */
    const DISTRIBUTION_NEXT_TICK = 'next_tick';

    /**
     * @var callable
     */
    private $middlewareChain;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var array
     */
    private $middleware;

    /**
     * @param LoopInterface $loop
     * @param array         $middleware
     * @param string        $distribution
     */
    public function __construct(
        LoopInterface $loop,
        array $middleware,
        string $distribution
    ) {
        $this->loop = $loop;
        $this->middleware = array_map(function (DebugableMiddleware $middleware) {
            return $middleware->getMiddlewareInfo();
        }, $middleware);

        $this->middlewareChain = self::DISTRIBUTION_NEXT_TICK === $distribution
            ? $this->createNextTickExecutionChain($middleware)
            : $this->createInlineExecutionChain($middleware);
    }

    /**
     * Executes the given command and optionally returns a value.
     *
     * @param object $command
     *
     * @return mixed
     *
     * @throws InvalidCommandException
     */
    protected function handle($command)
    {
        if (!is_object($command)) {
            throw new InvalidCommandException();
        }

        return ($this->middlewareChain)($command);
    }

    /**
     * Create inline execution chain.
     *
     * @param array $middlewareList
     *
     * @return callable
     */
    private function createInlineExecutionChain($middlewareList)
    {
        $lastCallable = function () {};

        while ($middleware = array_pop($middlewareList)) {
            $lastCallable = function ($command) use ($middleware, $lastCallable) {
                return $middleware->execute($command, $lastCallable);
            };
        }

        return $lastCallable;
    }

    /**
     * Create next tick execution chain.
     *
     * @param array $middlewareList
     *
     * @return callable
     */
    private function createNextTickExecutionChain($middlewareList)
    {
        $lastCallable = function () {};

        while ($middleware = array_pop($middlewareList)) {
            $lastCallable = function ($command) use ($middleware, $lastCallable) {
                $deferred = new Deferred();
                $this
                    ->loop
                    ->futureTick(function () use ($deferred, $middleware, $command, $lastCallable) {
                        $deferred->resolve($middleware->execute(
                            $command,
                            $lastCallable
                        ));
                    });

                return $deferred->promise();
            };
        }

        return $lastCallable;
    }

    /**
     * Get middleware list.
     *
     * @return array
     */
    public function getMiddlewareList(): array
    {
        return $this->middleware;
    }
}
