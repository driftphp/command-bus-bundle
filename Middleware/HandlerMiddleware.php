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

use Drift\CommandBus\Exception\MissingHandlerException;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class HandlerMiddleware.
 */
class HandlerMiddleware implements DebugableMiddleware
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
            return reject(new MissingHandlerException());
        }

        list($handler, $method) = $this->handlersMap[$commandOrQueryNamespace];
        $result = $handler->$method($command);

        return ($result instanceof PromiseInterface)
            ? $result
            : resolve($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareInfo(): array
    {
        return [
            'class' => self::class,
            'method' => 'execute',
            'handlers' => array_map(function (array $parts) {
                return [
                    'handler' => get_class($parts[0]),
                    'method' => $parts[1],
                ];
            }, $this->handlersMap),
        ];
    }
}
