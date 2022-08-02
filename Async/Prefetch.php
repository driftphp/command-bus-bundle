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

namespace Drift\CommandBus\Async;

/**
 * Class Prefetch.
 */
class Prefetch
{
    private int $prefetchSize;
    private int $prefetchCount;
    private bool $global;

    /**
     * @param int  $prefetchSize
     * @param int  $prefetchCount
     * @param bool $global
     */
    public function __construct(
        int $prefetchSize,
        int $prefetchCount,
        bool $global
    ) {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
        $this->global = $global;
    }

    /**
     * @return int
     */
    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    /**
     * @return int
     */
    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    /**
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->global;
    }
}
