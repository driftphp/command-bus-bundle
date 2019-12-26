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

use Drift\Bus\Exception\InvalidMiddlewareException;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

class Middleware
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
