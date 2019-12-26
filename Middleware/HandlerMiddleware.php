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

namespace Drift\Bus\Middleware;

use Drift\Bus\Exception\MissingHandlerException;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class HandlerMiddleware.
 */
class HandlerMiddleware
{
    /**
     * @var array
     */
    private $handlersMap = [];

    /**
     * Add handler.
     *
     * @param string $commandNamespace
     * @param object $handler
     * @param string $method
     */
    public function addHandler(
        string $commandNamespace,
        object $handler,
        string $method
    ) {
        $this->handlersMap[$commandNamespace] = [$handler, $method];
    }

    /**
     * Handle.
     *
     * @param object   $command
     * @param callable $next
     *
     * @return PromiseInterface
     *
     * @throws MissingHandlerException
     */
    public function execute($command, callable $next)
    {
        $commandOrQueryNamespace = get_class($command);

        if (!array_key_exists($commandOrQueryNamespace, $this->handlersMap)) {
            throw new MissingHandlerException();
        }

        list($handler, $method) = $this->handlersMap[$commandOrQueryNamespace];
        $result = $handler->$method($command);

        return ($result instanceof PromiseInterface)
            ? $result
            : new FulfilledPromise($result);
    }
}
