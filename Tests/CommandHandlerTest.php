<?php

namespace Drift\Bus\Tests;

use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use function Clue\React\Block\await;

/**
 * Class CommandHandlerTest
 */
class CommandHandlerTest extends BusFunctionalTest
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
        $configuration['services'][ChangeAThingHandler::class] = [
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
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
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('thing', $this->getContextValue('thing'));
        $this->assertNull($value);
    }
}