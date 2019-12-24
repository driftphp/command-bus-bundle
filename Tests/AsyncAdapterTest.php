<?php

namespace Drift\Bus\Tests;

use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\Bus\Tests\Middleware\Middleware1;
use function Clue\React\Block\await;

/**
 * Class AsyncAdapterTest
 */
abstract class AsyncAdapterTest extends BusFunctionalTest
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

        $configuration['imports'] = [
            ['resource' => __DIR__.'/autowiring.yml'],
        ];

        $configuration['bus'] = [
            'command_bus' => [
                'async_adapter' => static::getAsyncConfiguration(),
                'middlewares' => [
                    Middleware1::class . '::anotherMethod',
                ]
            ],
        ];

        return $configuration;
    }

    /**
     * Get async configuration
     *
     * @return array
     */
    abstract static protected function getAsyncConfiguration() : array;

    /**
     * Test buses are being built
     *
     * @group async
     */
    public function testQueryBus()
    {
        @unlink('/tmp/a.thing');
        $promise = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        await($promise, $this->getLoop());

        $this->assertNull($this->getContextValue('middleware1'));
        $this->assertFalse(file_exists('/tmp/a.thing'));
        $output = $this->runCommand([
            'bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertContains('Command <ChangeAThing> consumed', $output);
        $this->assertTrue($this->getContextValue('middleware1'));
        $this->assertTrue(file_exists('/tmp/a.thing'));
    }
}