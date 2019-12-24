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

namespace Drift\Bus\Adapter;

use Drift\Bus\Bus;
use League\Tactician\CommandBus;
use React\Promise\PromiseInterface;

/**
 * Class TacticianBus.
 */
class TacticianBus implements Bus
{
    /**
     * @var CommandBus
     */
    private $bus;

    /**
     * TacticianBus constructor.
     *
     * @param CommandBus $bus
     */
    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($object): PromiseInterface
    {
        return $this
            ->bus
            ->handle($object);
    }
}
