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

namespace Drift\CommandBus\Tests\Query;

/**
 * Class DoAThing.
 */
class GetAThing
{
    /**
     * @var string
     */
    private $thing;

    /**
     * DoAThing constructor.
     *
     * @param string $thing
     */
    public function __construct(string $thing)
    {
        $this->thing = $thing;
    }

    /**
     * @return string
     */
    public function getThing(): string
    {
        return $this->thing;
    }
}
