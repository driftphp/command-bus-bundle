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

namespace Drift\Bus\Tests\Bus;

use Drift\Bus\Exception\MissingHandlerException;
use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\QueryHandler\GetAThingHandler;

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
        $this
            ->getQueryBus()
            ->ask(new ChangeAThing('thing'));
    }
}
