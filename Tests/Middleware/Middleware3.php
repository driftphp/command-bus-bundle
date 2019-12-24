<?php

namespace Drift\Bus\Tests\Middleware;

use Drift\Bus\Tests\Context;

/**
 * Class Middleware3
 */
class Middleware3
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
    public function execute($command, callable $next)
    {
        return $next($command)
            ->then(function($value) {
                $this->context->values['middleware3'] = true;

                return $value;
            });
    }
}