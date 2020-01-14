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

use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\Context;

/**
 * ClassChangeAThingHandler.
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
     * Handle.
     */
    public function handle(ChangeAThing $AThing)
    {
        $this->context->values['thing'] = $AThing->getThing();

        touch('/tmp/a.thing');

        return $AThing->getThing().' OK';
    }
}
