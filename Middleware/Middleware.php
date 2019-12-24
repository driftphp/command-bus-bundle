<?php


namespace Drift\Bus\Middleware;

use Drift\Bus\Exception\BadMiddlewareException;
use League\Tactician\Middleware as BaseMiddleware;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;


class Middleware implements BaseMiddleware
{
    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * @var string
     */
    private $method;

    /**
     * Middleware constructor.
     *
     * @param object $middleware
     * @param string $method
     *
     * @throws BadMiddlewareException
     */
    public function __construct(
        $middleware,
        string $method
    )
    {
        if (!method_exists($middleware, $method)) {
            throw new BadMiddlewareException();
        }

        $this->middleware = $middleware;
        $this->method = $method;
    }

    /**
     * @inheritDoc
     */
    public function execute($command, callable $next)
    {
        $result = $this
            ->middleware
            ->{$this->method}($command, $next);

        return ($result instanceof PromiseInterface)
            ? $result
            : new FulfilledPromise($result);
    }
}