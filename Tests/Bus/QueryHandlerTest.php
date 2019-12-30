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

use Drift\Bus\Exception\InvalidCommandException;
use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Query\GetAThing;
use Drift\Bus\Tests\QueryHandler\GetAThingHandler;
use function Clue\React\Block\await;

/**
 * Class QueryHandlerTest.
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
            ],
        ];

        return $configuration;
    }

    /**
     * Test buses are being built.
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

    /**
     * Test bad command.
     */
    public function testBadCommand()
    {
        $this->expectException(InvalidCommandException::class);
        $promise = $this
            ->getQueryBus()
            ->ask('something');

        await($promise, $this->getLoop());
    }
}
