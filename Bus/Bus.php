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

namespace Drift\Bus\Bus;

use Drift\Bus\Exception\InvalidCommandException;

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
     * @param array $middleware
     */
    public function __construct(array $middleware)
    {
        $this->middlewareChain = $this->createExecutionChain($middleware);
    }

    /**
     * Executes the given command and optionally returns a value
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
     * Create execution chain
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
}
