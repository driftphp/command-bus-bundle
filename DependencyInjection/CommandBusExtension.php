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

use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class BusExtension.
 */
class CommandBusExtension extends BaseExtension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'command_bus';
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
        return new CommandBusConfiguration($this->getAlias());
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
            'bus.query_bus.middlewares' => $config['query_bus']['middlewares'],
            'bus.query_bus.distribution' => $config['query_bus']['distribution'],
            'bus.command_bus.middlewares' => $config['command_bus']['middlewares'],
            'bus.command_bus.distribution' => $config['command_bus']['distribution'],
            'bus.command_bus.async_adapter' => $config['command_bus']['async_adapter'] ?? false,
        ];
    }
}
