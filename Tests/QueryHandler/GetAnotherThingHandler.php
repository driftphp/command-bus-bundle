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

namespace Drift\Bus\Tests\QueryHandler;

use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Query\GetAnotherThing;

/**
 * Class DonotherAThing handler.
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
     * Handle.
     */
    public function another(GetAnotherThing $AnotherThing)
    {
        $this->context->values['thing'] = $AnotherThing->getThing();

        return $AnotherThing->getThing().' OK!!';
    }
}
