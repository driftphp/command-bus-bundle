<?php

namespace Drift\Bus\DependencyInjection;


use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class BusExtension extends BaseExtension
{
    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return 'bus';
    }

    /**
     * Return a new Configuration instance.
     *
     * If object returned by this method is an instance of
     * ConfigurationInterface, extension will use the Configuration to read all
     * bundle config definitions.
     *
     * Also will call getParametrizationValues method to load some config values
     * to internal parameters.
     *
     * @return ConfigurationInterface|null
     */
    protected function getConfigurationInstance(): ? ConfigurationInterface
    {
        return new BusConfiguration($this->getAlias());
    }

    /**
     * Load Parametrization definition.
     *
     * return array(
     *      'parameter1' => $config['parameter1'],
     *      'parameter2' => $config['parameter2'],
     *      ...
     * );
     *
     * @param array $config Bundles config values
     *
     * @return array
     */
    protected function getParametrizationValues(array $config): array
    {
        return [
            'bus.adapter' => $config['adapter'],
            'bus.query_bus.middlewares' => $config['query_bus']['middlewares'],
            'bus.command_bus.middlewares' => $config['command_bus']['middlewares'],
            'bus.command_bus.async_adapter' => $config['command_bus']['async_adapter'] ?? false
        ];
    }
}