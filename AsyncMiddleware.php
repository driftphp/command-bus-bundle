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

namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Class AsyncMiddleware.
 */
class AsyncMiddleware
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
}
