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

namespace Drift\Bus\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class BusConfiguration extends BaseConfiguration
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
                        ->arrayNode('middlewares')
                            ->scalarPrototype()
                                ->defaultValue([])
                            ->end()
                        ->end()
                        ->arrayNode('async_adapter')
                            ->children()
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
