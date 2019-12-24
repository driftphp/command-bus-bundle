<?php

namespace Drift\Bus\Tests\QueryHandler;

use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Query\GetAnotherThing;
use Drift\Bus\Tests\Query\GetAThing;

/**
 * Class DonotherAThing handler
 */
final class GetAnotherThingHandler
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
    public function another(GetAnotherThing $AnotherThing)
    {
        $this->context->values['thing'] = $AnotherThing->getThing();

        return $AnotherThing->getThing() . ' OK!!';
    }
}