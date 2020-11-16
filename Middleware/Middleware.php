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

namespace Drift\CommandBus\Middleware;

use Drift\CommandBus\Exception\InvalidMiddlewareException;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class Middleware.
 */
class Middleware implements DebugableMiddleware
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
     * @throws InvalidMiddlewareException
     */
    public function __construct(
        $middleware,
        string $method
    ) {
        if (!method_exists($middleware, $method)) {
            throw new InvalidMiddlewareException();
        }

        $this->middleware = $middleware;
        $this->method = $method;
    }

    /**
     * Execute middleware.
     *
     * @param $command
     * @param callable $next
     *
     * @return PromiseInterface
     */
    public function execute($command, callable $next): PromiseInterface
    {
        $middleware = $this->middleware;

        if ($middleware instanceof DiscriminableMiddleware) {
            $onlyHandle = $middleware->onlyHandle();

            if (empty(array_intersect($onlyHandle, $this->getNamespaceCollectionOfClass($command)))) {
                return $next($command);
            }
        }

        $result = $this
            ->middleware
            ->{$this->method}($command, $next);

        return ($result instanceof PromiseInterface)
            ? $result
            : resolve($result);
    }

    /**
     * Get inner middleware info.
     *
     * @return array
     */
    public function getMiddlewareInfo(): array
    {
        $info = [
            'class' => get_class($this->middleware),
            'method' => $this->method,
        ];

        if ($this->middleware instanceof DiscriminableMiddleware) {
            $info['handled_objects'] = $this
                ->middleware
                ->onlyHandle();
        }

        return $info;
    }

    /**
     * Return class namespace, all parent namespaces and interfaces of a class.
     *
     * @param object $object
     *
     * @return string[]
     */
    private function getNamespaceCollectionOfClass($object): array
    {
        return array_merge(
            [get_class($object)],
            class_parents($object, false),
            class_implements($object, false)
        );
    }
}
