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

namespace Drift\Bus;

use Drift\Bus\DependencyInjection\BusExtension;
use Drift\Bus\DependencyInjection\CompilerPass\BusCompilerPass;
use Mmoreram\BaseBundle\BaseBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Class BusBundle.
 */
class BusBundle extends BaseBundle
{
    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return new BusExtension();
    }

    /**
     * Return a CompilerPass instance array.
     *
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses(): array
    {
        return [
            new BusCompilerPass(),
        ];
    }
}
