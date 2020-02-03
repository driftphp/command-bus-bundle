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

namespace Drift\CommandBus\DependencyInjection;

use Drift\CommandBus\Bus\Bus;
use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class CommandBusConfiguration.
 */
class CommandBusConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('query_bus')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('distribution')
                            ->values([Bus::DISTRIBUTION_INLINE, Bus::DISTRIBUTION_NEXT_TICK])
                            ->defaultValue(Bus::DISTRIBUTION_INLINE)
                        ->end()
                        ->arrayNode('middlewares')
                            ->scalarPrototype()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('command_bus')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('distribution')
                            ->values([Bus::DISTRIBUTION_INLINE, Bus::DISTRIBUTION_NEXT_TICK])
                            ->defaultValue(Bus::DISTRIBUTION_INLINE)
                        ->end()
                        ->arrayNode('middlewares')
                            ->scalarPrototype()
                                ->defaultValue([])
                            ->end()
                        ->end()
                        ->arrayNode('async_adapter')
                            ->children()
                                ->enumNode('adapter')
                                    ->values(['in_memory', 'amqp', 'redis'])
                                ->end()
                                ->arrayNode('in_memory')->end()
                                ->arrayNode('amqp')
                                    ->children()
                                        ->scalarNode('client')->end()
                                        ->scalarNode('queue')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('redis')
                                    ->children()
                                        ->scalarNode('client')->end()
                                        ->scalarNode('key')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
