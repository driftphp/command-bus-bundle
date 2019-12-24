<?php

namespace Drift\Bus\Tests\CommandHandler;

use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\Context;

/**
 * ClassChangeAThingHandler
 */
final class ChangeAThingHandler
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
    public function handle(ChangeAThing $AThing)
    {
        $this->context->values['thing'] = $AThing->getThing();

        touch('/tmp/a.thing');

        return $AThing->getThing() . ' OK';
    }
}