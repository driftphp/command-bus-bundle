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

use Drift\CommandBus\Middleware\DiscriminableMiddleware;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\Context;

/**
 * Class DiscriminableMiddleware1.
 */
class DiscriminableMiddleware1 implements DiscriminableMiddleware
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

    public function execute($command, callable $next)
    {
        return $next($command)
            ->then(function ($value) use ($command) {
                $this->context->values[get_class($command)] = true;

                return $value;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function onlyHandle(): array
    {
        return [
            ChangeAThing::class,
        ];
    }
}
