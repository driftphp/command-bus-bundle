<?php

namespace Drift\Bus\Tests;

use Drift\Bus\Tests\Query\GetAThing;
use Drift\Bus\Tests\QueryHandler\GetAThingHandler;
use function Clue\React\Block\await;

/**
 * Class QueryHandlerTest
 */
class QueryHandlerTest extends BusFunctionalTest
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
                ['name' => 'another_tag', 'method' => 'anotherMethod'],
            ]
        ];

        return $configuration;
    }

    /**
     * Test buses are being built
     */
    public function testQueryBus()
    {
        $promise = $this
            ->getQueryBus()
            ->ask(new GetAThing('thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('thing', $this->getContextValue('thing'));
        $this->assertEquals('thing OK', $value);
    }
}