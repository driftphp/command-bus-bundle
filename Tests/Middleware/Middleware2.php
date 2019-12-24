<?php

namespace Drift\Bus\Tests\Middleware;

use League\Tactician\Middleware;

/**
 * Class Middleware2
 */
class Middleware2 extends Middleware1 implements Middleware
{
    /**
     * @inheritDoc
     */
    public function execute($command, callable $next)
    {
        return $next($command)
            ->then(function($value) {
                $this->context->values['middleware1'] = true;

                return 'super ' . $value;
            });
    }
}