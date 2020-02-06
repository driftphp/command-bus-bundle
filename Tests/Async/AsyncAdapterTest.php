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

namespace Drift\CommandBus\Tests\Async;

use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Command\ChangeAnotherThing;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\Command\ChangeBThing;
use Drift\CommandBus\Tests\Command\ChangeYetAnotherThing;
use Drift\CommandBus\Tests\CommandHandler\ChangeAnotherThingHandler;
use Drift\CommandBus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\CommandBus\Tests\CommandHandler\ChangeYetAnotherThingHandler;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Middleware\Middleware1;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;

/**
 * Class AsyncAdapterTest.
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
            ],
        ];

        $configuration['services'][ChangeAnotherThingHandler::class] = [
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
            ],
        ];

        $configuration['services'][ChangeYetAnotherThingHandler::class] = [
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
            ],
        ];

        $configuration['imports'] = [
            ['resource' => __DIR__.'/../autowiring.yml'],
        ];

        $configuration['command_bus'] = [
            'command_bus' => [
                'async_adapter' => static::getAsyncConfiguration(),
                'middlewares' => [
                    Middleware1::class.'::anotherMethod',
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * Get async configuration.
     *
     * @return array
     */
    abstract protected static function getAsyncConfiguration(): array;

    /**
     * Test infrastructure.
     */
    public function testInfrastructure()
    {
        $output = $this->dropInfrastructure();
        $this->assertContains('dropped', $output);

        $output = $this->createInfrastructure();
        $this->assertContains('created properly', $output);

        $output = $this->checkInfrastructure();
        $this->assertContains('exists', $output);

        $output = $this->dropInfrastructure();
        $this->assertContains('dropped', $output);
    }

    /**
     * Test by reading only 1 command.
     *
     * @group async1
     */
    public function test1Command()
    {
        @unlink('/tmp/a.thing');
        $this->resetInfrastructure();

        $promise1 = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        $promise2 = $this
            ->getCommandBus()
            ->execute(new ChangeBThing());

        $promise3 = $this
            ->getCommandBus()
            ->execute(new ChangeAnotherThing('thing'));

        await($promise1, $this->getLoop());
        await($promise2, $this->getLoop());
        await($promise3, $this->getLoop());

        $this->assertNull($this->getContextValue('middleware1'));
        $this->assertFalse(file_exists('/tmp/a.thing'));
        $output = $this->runCommand([
            'command-bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertContains("\033[01;32mConsumed\033[0m ChangeAThing", $output);
        $this->assertNotContains("\033[01;32mConsumed\033[0m ChangeAnotherThing", $output);
        $this->assertTrue($this->getContextValue('middleware1'));
        $this->assertTrue(file_exists('/tmp/a.thing'));

        $output2 = $this->runCommand([
            'command-bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertNotContains("\033[01;32mConsumed\033[0m ChangeAThing", $output2);
        $this->assertContains("\033[01;36mIgnored \033[0m ChangeBThing", $output2);
        $this->assertContains("\033[01;32mConsumed\033[0m ChangeAnotherThing", $output2);
    }

    /**
     * Test by reading 2 commands.
     *
     * @group async2
     */
    public function test2Commands()
    {
        $this->resetInfrastructure();

        $promises[] = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        $promises[] = $this
            ->getCommandBus()
            ->execute(new ChangeAnotherThing('thing'));

        $promises[] = $this
            ->getCommandBus()
            ->execute(new ChangeYetAnotherThing('thing'));

        awaitAll($promises, $this->getLoop());

        $output = $this->runCommand([
            'command-bus:consume-commands',
            '--limit' => 2,
        ]);

        $this->assertContains("\033[01;32mConsumed\033[0m ChangeAThing", $output);
        $this->assertContains("\033[01;32mConsumed\033[0m ChangeAnotherThing", $output);
        $this->assertNotContains("\033[01;32mConsumed\033[0m ChangeYetAnotherThing", $output);

        $output = $this->runCommand([
            'command-bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertNotContains("\033[01;32mConsumed\033[0m ChangeAThing", $output);
        $this->assertNotContains("\033[01;32mConsumed\033[0m ChangeAnotherThing", $output);
        $this->assertContains("\033[01;32mConsumed\033[0m ChangeYetAnotherThing", $output);
    }

    /**
     * Reset infrastructure.
     */
    private function resetInfrastructure()
    {
        $this->dropInfrastructure();
        $this->createInfrastructure();
    }

    /**
     * Drop infrastructure.
     *
     * @return string
     */
    private function dropInfrastructure(): string
    {
        return $this->runCommand([
            'command-bus:infra:drop',
            '--force' => true,
        ]);
    }

    /**
     * Create infrastructure.
     *
     * @return string
     */
    private function createInfrastructure(): string
    {
        return $this->runCommand([
            'command-bus:infra:create',
            '--force' => true,
        ]);
    }

    /**
     * Check infrastructure.
     *
     * @return string
     */
    private function checkInfrastructure(): string
    {
        return $this->runCommand([
            'command-bus:infra:check',
        ]);
    }
}
