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

namespace Drift\Bus\Tests\Async;

use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\Command\ChangeAnotherThing;
use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\Command\ChangeBThing;
use Drift\Bus\Tests\Command\ChangeYetAnotherThing;
use Drift\Bus\Tests\CommandHandler\ChangeAnotherThingHandler;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\Bus\Tests\CommandHandler\ChangeYetAnotherThingHandler;
use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Middleware\Middleware1;
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

        $configuration['bus'] = [
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
     * Test by reading only 1 command.
     *
     * @group async1
     */
    public function test1Command()
    {
        @unlink('/tmp/a.thing');
        $promise1 = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        $promise2 = $this
            ->getCommandBus()
            ->execute(new ChangeAnotherThing('thing'));

        $promise3 = $this
            ->getCommandBus()
            ->execute(new ChangeBThing());

        await($promise1, $this->getLoop());
        await($promise2, $this->getLoop());
        await($promise3, $this->getLoop());

        $this->assertNull($this->getContextValue('middleware1'));
        $this->assertFalse(file_exists('/tmp/a.thing'));
        $output = $this->runCommand([
            'bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertContains('Command <ChangeAThing> consumed', $output);
        $this->assertNotContains('Command <ChangeAnotherThing> consumed', $output);
        $this->assertTrue($this->getContextValue('middleware1'));
        $this->assertTrue(file_exists('/tmp/a.thing'));

        $output2 = $this->runCommand([
            'bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertNotContains('Command <ChangeAThing> consumed', $output2);
        $this->assertContains('Command <ChangeAnotherThing> consumed', $output2);
    }

    /**
     * Test by reading 2 commands.
     *
     * @group async2
     */
    public function test2Commands()
    {
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
            'bus:consume-commands',
            '--limit' => 2,
        ]);

        $this->assertContains('Command <ChangeAThing> consumed', $output);
        $this->assertContains('Command <ChangeAnotherThing> consumed', $output);
        $this->assertNotContains('Command <ChangeYetAnotherThing> consumed', $output);

        $output = $this->runCommand([
            'bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertNotContains('Command <ChangeAThing> consumed', $output);
        $this->assertNotContains('Command <ChangeAnotherThing> consumed', $output);
        $this->assertContains('Command <ChangeYetAnotherThing> consumed', $output);
    }
}
