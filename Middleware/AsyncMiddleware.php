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

use Drift\CommandBus\Async\AsyncAdapter;
use React\Promise\PromiseInterface;

/**
 * Class AsyncMiddleware.
 */
class AsyncMiddleware implements DebugableMiddleware
{
    /**
     * @var AsyncAdapter
     */
    private $asyncAdapter;

    /**
     * AsyncMiddleware constructor.
     *
     * @param AsyncAdapter $asyncAdapter
     */
    public function __construct(AsyncAdapter $asyncAdapter)
    {
        $this->asyncAdapter = $asyncAdapter;
    }

    /**
     * @return PromiseInterface
     */
    public function prepare(): PromiseInterface
    {
        return $this
            ->asyncAdapter
            ->prepare()
            ->then(function () {
                return $this;
            });
    }

    /**
     * Handle.
     *
     * @param object $command
     * @param object $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        return $this
            ->asyncAdapter
            ->enqueue($command);
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareInfo(): array
    {
        return [
            'class' => self::class,
            'method' => 'execute',
        ];
    }
}
