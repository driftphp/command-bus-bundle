<?php

namespace Drift\Bus\Tests;

use Drift\Bus\Exception\MissingHandlerException;
use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\QueryHandler\GetAThingHandler;

/**
 * Class QueryHandlerNotExistsTest
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
                ['name' => 'query_handler', 'method' => 'handle']
            ]
        ];

        return $configuration;
    }

    /**
     * Test buses are being built
     */
    public function testQueryBus()
    {
        $this->expectException(MissingHandlerException::class);
        $this
            ->getQueryBus()
            ->ask(new ChangeAThing('thing'));
    }
}