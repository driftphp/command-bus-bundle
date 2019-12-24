<?php

namespace Drift\Bus\Middleware;

use Drift\Bus\Exception\MissingHandlerException;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use League\Tactician\Middleware as BaseMiddleware;

/**
 * Class HandlerMiddleware
 */
class HandlerMiddleware implements BaseMiddleware
{
    /**
     * @var array
     */
    private $handlersMap = [];

    /**
     * Add handler
     *
     * @param string $commandNamespace
     * @param object $handler
     * @param string $method
     */
    public function addHandler(
        string $commandNamespace,
        object $handler,
        string $method
    )
    {
        $this->handlersMap[$commandNamespace] = [$handler, $method];
    }

    /**
     * Handle
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