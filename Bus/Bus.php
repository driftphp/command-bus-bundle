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
use Drift\CommandBus\Middleware\Middleware;

/**
 * Interface Bus.
 */
abstract class Bus
{
    /**
     * @var callable
     */
    private $middlewareChain;

    /**
     * @var array
     */
    private $middleware;

    /**
     * @param array $middleware
     */
    public function __construct(array $middleware)
    {
        $this->middleware = array_map(function (DebugableMiddleware $middleware) {
            return $middleware->getMiddlewareInfo();
        }, $middleware);
        $this->middlewareChain = $this->createExecutionChain($middleware);
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
     * Create execution chain.
     *
     * @param array $middlewareList
     *
     * @return callable
     */
    private function createExecutionChain($middlewareList)
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
     * Get middleware list.
     *
     * @return array
     */
    public function getMiddlewareList(): array
    {
        return $this->middleware;
    }
}
