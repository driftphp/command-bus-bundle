<?php

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
                ->enumNode('adapter')
                    ->values([
                        'tactician'
                    ])
                    ->defaultValue('tactician')
                ->end()

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
                                ->arrayNode('filesystem')
                                    ->children()
                                        ->scalarNode('file')->end()
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