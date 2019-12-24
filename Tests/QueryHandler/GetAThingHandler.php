<?php

namespace Drift\Bus\Tests\QueryHandler;

use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Query\GetAThing;

/**
 * Class DoAThing handler
 */
final class GetAThingHandler
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
     * Handle
     */
    public function handle(GetAThing $AThing)
    {
        $this->context->values['thing'] = $AThing->getThing();

        return $AThing->getThing() . ' OK';
    }
}