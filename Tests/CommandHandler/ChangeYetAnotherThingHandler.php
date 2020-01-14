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

namespace Drift\CommandBus\Tests\CommandHandler;

use Drift\CommandBus\Tests\Command\ChangeYetAnotherThing;
use Drift\CommandBus\Tests\Context;

/**
 * ChangeYetAnotherThingHandler.
 */
final class ChangeYetAnotherThingHandler
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
     * Handle.
     */
    public function handle(ChangeYetAnotherThing $AnotherThing)
    {
        $this->context->values['thing'] = $AnotherThing->getThing();

        return $AnotherThing->getThing().' YET!!';
    }
}
