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

namespace Drift\CommandBus\Tests\Middleware;

use Drift\CommandBus\Tests\Context;

/**
 * Class Middleware1.
 */
class Middleware1
{
    /**
     * @var Context
     */
    public $context;

    /**
     * DoAThingHandler constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function anotherMethod($command, callable $next)
    {
        return $next($command)
            ->then(function ($value) {
                $this->context->values['middleware1'] = true;

                return $value.' changed';
            });
    }
}
