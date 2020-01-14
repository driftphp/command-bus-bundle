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

namespace Drift\CommandBus\Tests\Bus;

use Drift\CommandBus\Exception\MissingHandlerException;
use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\QueryHandler\GetAThingHandler;
use function Clue\React\Block\await;

/**
 * Class QueryHandlerNotExistsTest.
 */
class QueryHandlerNotExistsTest extends BusFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration['services'][Context::class] = [];
        $configuration['services'][GetAThingHandler::class] = [
            'tags' => [
                ['name' => 'query_handler', 'method' => 'handle'],
            ],
        ];

        return $configuration;
    }

    /**
     * Test buses are being built.
     */
    public function testQueryBus()
    {
        $this->expectException(MissingHandlerException::class);
        $promise = $this
            ->getQueryBus()
            ->ask(new ChangeAThing('thing'));

        await($promise, $this->getLoop());
    }
}
