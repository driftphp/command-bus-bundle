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

namespace Drift\Bus\Tests\Middleware;

use League\Tactician\Middleware;

/**
 * Class Middleware2.
 */
class Middleware2 extends Middleware1 implements Middleware
{
    /**
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        return $next($command)
            ->then(function ($value) {
                $this->context->values['middleware1'] = true;

                return 'super '.$value;
            });
    }
}
