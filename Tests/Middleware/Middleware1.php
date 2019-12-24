<?php

namespace Drift\Bus\Tests\Middleware;

use Drift\Bus\Tests\Context;

/**
 * Class Middleware1
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

    /**
     * @inheritDoc
     */
    public function anotherMethod($command, callable $next)
    {
        return $next($command)
            ->then(function($value) {
                $this->context->values['middleware1'] = true;

                return $value . ' changed';
            });
    }
}